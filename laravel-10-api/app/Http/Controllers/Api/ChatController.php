<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    /** Kirim pesan ke chatbot. */
    public function kirim(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|string|max:64',
            'message' => 'required|string|max:1000',
        ]);

        // Rate limit per sesi (tersimpan di cache/Redis): 20 pesan per menit
        $kunci = 'chat-limit:' . $data['session_id'];
        if (!RateLimiter::attempt($kunci, 20, fn() => true, 60)) {
            return response()->json([
                'success' => false,
                'message' => 'Pelan-pelan ya — terlalu banyak pesan. Coba lagi sebentar lagi.',
            ], 429);
        }

        $hasil = ChatbotService::balas($data['session_id'], trim($data['message']));

        return response()->json([
            'success' => true,
            'reply' => $hasil['reply'],
            'source' => $hasil['source'],
            'history' => $hasil['history'],
        ]);
    }

    /** Ambil riwayat percakapan (dari Redis/cache). */
    public function riwayat(string $sessionId)
    {
        return response()->json([
            'success' => true,
            'history' => ChatbotService::riwayat($sessionId),
            'n8n' => ChatbotService::n8nAktif(),
        ]);
    }

    /** Bersihkan riwayat. */
    public function hapus(string $sessionId)
    {
        ChatbotService::hapusRiwayat($sessionId);
        return response()->json(['success' => true]);
    }
}
