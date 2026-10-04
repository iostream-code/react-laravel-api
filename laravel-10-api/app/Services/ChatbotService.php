<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatbotService
{
    private const TTL_RIWAYAT = 3600; // riwayat chat disimpan 1 jam (Redis/cache)
    private const MAKS_RIWAYAT = 20;

    /** n8n aktif bila URL webhook diisi di .env. */
    public static function n8nAktif(): bool
    {
        return (bool) config('services.n8n.webhook_url');
    }

    /** Proses satu pesan: coba n8n dulu, jatuh ke bot bawaan bila gagal. */
    public static function balas(string $sessionId, string $pesan): array
    {
        $riwayat = self::riwayat($sessionId);

        $jawaban = null;
        $sumber = 'bot';

        if (self::n8nAktif()) {
            try {
                $res = Http::timeout(15)->post(config('services.n8n.webhook_url'), [
                    'session_id' => $sessionId,
                    'message' => $pesan,
                    'history' => $riwayat,
                ]);
                if ($res->successful() && filled($res->json('reply'))) {
                    $jawaban = (string) $res->json('reply');
                    $sumber = 'n8n';
                }
            } catch (\Throwable) {
                // n8n tidak terjangkau — pakai bot bawaan
            }
        }

        $jawaban ??= self::botBawaan($pesan);

        $riwayat[] = ['role' => 'user', 'text' => $pesan, 'at' => now()->toIso8601String()];
        $riwayat[] = ['role' => 'bot', 'text' => $jawaban, 'at' => now()->toIso8601String()];
        $riwayat = array_slice($riwayat, -self::MAKS_RIWAYAT);
        Cache::put(self::kunci($sessionId), $riwayat, self::TTL_RIWAYAT);

        return ['reply' => $jawaban, 'source' => $sumber, 'history' => $riwayat];
    }

    public static function riwayat(string $sessionId): array
    {
        return Cache::get(self::kunci($sessionId), []);
    }

    public static function hapusRiwayat(string $sessionId): void
    {
        Cache::forget(self::kunci($sessionId));
    }

    private static function kunci(string $sessionId): string
    {
        return 'chat:' . $sessionId;
    }

    /** Bot aturan sederhana — selalu tersedia tanpa n8n. */
    private static function botBawaan(string $pesan): string
    {
        $p = Str::lower($pesan);

        return match (true) {
            Str::contains($p, ['halo', 'hai', 'hi', 'assalamu', 'pagi', 'siang', 'malam']) =>
                'Halo! 👋 Saya asisten blog ini. Tanyakan "berapa post?", "post terbaru", atau ketik "bantuan".',

            Str::contains($p, ['berapa post', 'jumlah post', 'total post']) =>
                'Saat ini ada ' . Post::count() . ' post di blog ini. 📝',

            Str::contains($p, ['terbaru', 'post baru']) =>
                ($post = Post::latest()->first())
                    ? "Post terbaru: \"{$post->title}\"."
                    : 'Belum ada post. Jadilah yang pertama menulis!',

            Str::contains($p, ['cara', 'bagaimana', 'buat post', 'tambah post']) =>
                'Untuk menambah post: buka menu POSTS lalu klik tombol "ADD NEW POST", isi judul, konten, dan gambar. ✍️',

            Str::contains($p, ['bantuan', 'help', 'menu']) =>
                "Saya bisa bantu:\n• \"berapa post?\" — jumlah post\n• \"post terbaru\" — judul terakhir\n• \"cara buat post\" — panduan singkat\n(Sambungkan n8n untuk jawaban AI yang lebih pintar!)",

            Str::contains($p, ['terima kasih', 'makasih', 'thanks']) =>
                'Sama-sama! 🙌',

            default =>
                'Hmm, saya belum paham maksudnya. Coba ketik "bantuan" untuk melihat yang bisa saya jawab — atau sambungkan n8n agar saya lebih pintar. 🤖',
        };
    }
}
