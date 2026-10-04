<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_bawaan_menjawab_tanpa_n8n(): void
    {
        config(['services.n8n.webhook_url' => '']);

        $res = $this->postJson('/api/chat', [
            'session_id' => 'tes-sesi-1',
            'message' => 'halo',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'bot');
        $this->assertStringContainsString('asisten', strtolower($res->json('reply')));
    }

    public function test_bot_tahu_jumlah_post(): void
    {
        Post::create(['title' => 'Post Tes', 'content' => 'Isi', 'image' => 'x.png']);

        $res = $this->postJson('/api/chat', [
            'session_id' => 'tes-sesi-2',
            'message' => 'berapa post?',
        ]);

        $this->assertStringContainsString('1 post', $res->json('reply'));
    }

    public function test_riwayat_tersimpan_dan_bisa_dihapus(): void
    {
        $this->postJson('/api/chat', ['session_id' => 'tes-sesi-3', 'message' => 'halo']);

        $this->getJson('/api/chat/tes-sesi-3')
            ->assertOk()
            ->assertJsonCount(2, 'history'); // user + bot

        $this->deleteJson('/api/chat/tes-sesi-3')->assertOk();
        $this->getJson('/api/chat/tes-sesi-3')->assertJsonCount(0, 'history');
    }

    public function test_validasi_pesan_kosong(): void
    {
        $this->postJson('/api/chat', ['session_id' => 'x', 'message' => ''])
            ->assertStatus(422);
    }

    public function test_cache_daftar_post_di_bust_saat_data_berubah(): void
    {
        $lama = Post::create(['title' => 'Lama', 'content' => 'Isi', 'image' => 'x.png']);
        $lama->created_at = now()->subMinute();
        $lama->save();

        $sebelum = $this->getJson('/api/post')->json('data.data.0.title');
        $this->assertSame('Lama', $sebelum);

        // Mutasi lewat model + bust manual mensimulasikan controller store()
        Post::create(['title' => 'Baru', 'content' => 'Isi', 'image' => 'y.png']);
        // Tanpa bust, cache lama masih berlaku
        $masihLama = $this->getJson('/api/post')->json('data.data.0.title');
        $this->assertSame('Lama', $masihLama);

        Cache::increment('posts:ver');
        $sesudah = $this->getJson('/api/post')->json('data.data.0.title');
        $this->assertSame('Baru', $sesudah);
    }
}
