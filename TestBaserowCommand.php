<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BaserowService;
use Illuminate\Support\Facades\Http;
use Exception;

class TestBaserowCommand extends Command
{
    protected $signature = 'baserow:test';
    protected $description = 'Test Baserow API connection and configuration';

    public function handle()
    {
        $this->info('🧪 Testing Baserow Configuration...');
        $this->newLine();

        // Test 1: Check environment variables
        $this->testEnvironmentConfig();
        
        // Test 2: Test API connection
        $this->testApiConnection();
        
        // Test 3: Test table access
        $this->testTableAccess();
        
        // Test 4: Test BaserowService
        $this->testBaserowService();

        return 0;
    }

    private function testEnvironmentConfig(): void
    {
        $this->line('1️⃣ <fg=cyan>Checking Environment Configuration</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $baseUrl = config('baserow.base_url');
        $token = config('baserow.token');
        $tableId = config('baserow.table_id');
        $columnName = config('baserow.video_url_column');
        
        $this->line("🌐 Base URL: " . ($baseUrl ?: '<fg=red>NOT SET</fg=red>'));
        $this->line("🔑 Token: " . ($token ? '<fg=green>SET (' . strlen($token) . ' chars)</fg=green>' : '<fg=red>NOT SET</fg=red>'));
        $this->line("📊 Table ID: " . ($tableId ?: '<fg=red>NOT SET</fg=red>'));
        $this->line("📋 Column Name: " . ($columnName ?: '<fg=red>NOT SET</fg=red>'));
        
        if (!$token || !$tableId) {
            $this->newLine();
            $this->error('❌ Missing required configuration. Please check your .env file:');
            $this->line('BASEROW_TOKEN=your_token_here');
            $this->line('BASEROW_TABLE_ID=your_table_id_here');
            return;
        }
        
        $this->newLine();
    }

    private function testApiConnection(): void
    {
        $this->line('2️⃣ <fg=cyan>Testing API Connection</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $baseUrl = config('baserow.base_url');
        $token = config('baserow.token');
        
        if (!$token) {
            $this->error('❌ Cannot test API - token not configured');
            $this->newLine();
            return;
        }
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Token ' . $token,
            ])->get($baseUrl . '/api/user/');
            
            if ($response->successful()) {
                $userData = $response->json();
                $this->line("✅ API connection successful!");
                $this->line("👤 User: " . ($userData['first_name'] ?? 'Unknown') . ' ' . ($userData['last_name'] ?? ''));
                $this->line("📧 Email: " . ($userData['username'] ?? 'Unknown'));
            } else {
                $this->error("❌ API connection failed: HTTP " . $response->status());
                $this->line("Response: " . $response->body());
            }
            
        } catch (Exception $e) {
            $this->error("❌ API connection error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testTableAccess(): void
    {
        $this->line('3️⃣ <fg=cyan>Testing Table Access</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $baseUrl = config('baserow.base_url');
        $token = config('baserow.token');
        $tableId = config('baserow.table_id');
        
        if (!$token || !$tableId) {
            $this->error('❌ Cannot test table - missing configuration');
            $this->newLine();
            return;
        }
        
        try {
            // Test getting table structure
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Token ' . $token,
            ])->get("{$baseUrl}/api/database/fields/table/{$tableId}/");
            
            if ($response->successful()) {
                $fields = $response->json();
                $this->line("✅ Table access successful!");
                $this->line("📊 Table has " . count($fields) . " fields:");
                
                $videoUrlColumn = config('baserow.video_url_column');
                $foundVideoColumn = false;
                
                foreach ($fields as $field) {
                    $name = $field['name'] ?? 'Unknown';
                    $type = $field['type'] ?? 'Unknown';
                    
                    if ($name === $videoUrlColumn) {
                        $this->line("   🎯 <fg=green>{$name}</fg=green> ({$type}) ← Target column");
                        $foundVideoColumn = true;
                    } else {
                        $this->line("   • {$name} ({$type})");
                    }
                }
                
                if (!$foundVideoColumn) {
                    $this->newLine();
                    $this->warn("⚠️  Target column '{$videoUrlColumn}' not found in table!");
                }
                
            } else {
                $this->error("❌ Table access failed: HTTP " . $response->status());
                $this->line("Response: " . $response->body());
            }
            
        } catch (Exception $e) {
            $this->error("❌ Table access error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testBaserowService(): void
    {
        $this->line('4️⃣ <fg=cyan>Testing BaserowService</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        try {
            $service = new BaserowService();
            
            // Test getting first row
            $this->line("🔍 Testing first row retrieval...");
            $firstRowUrl = $service->getVideoUrlFromFirstRow();
            
            if ($firstRowUrl) {
                $this->line("✅ First row video URL: <fg=green>{$firstRowUrl}</fg=green>");
            } else {
                $this->warn("⚠️  No video URL found in first row");
            }
            
            // Test getting all completed videos
            $this->line("🔍 Testing completed videos retrieval...");
            $completedVideos = $service->getAllCompletedVideos();
            $count = count($completedVideos);
            
            $this->line("✅ Found <fg=blue>{$count}</fg=blue> rows with video URLs");
            
            if ($count > 0) {
                $this->line("📝 Sample URLs:");
                $sampleSize = min(3, $count);
                for ($i = 0; $i < $sampleSize; $i++) {
                    $url = $completedVideos[$i]['video_url'];
                    $this->line("   • " . (strlen($url) > 60 ? substr($url, 0, 57) . '...' : $url));
                }
            }
            
        } catch (Exception $e) {
            $this->error("❌ BaserowService error: " . $e->getMessage());
        }
        
        $this->newLine();
        $this->line('🎉 <fg=green>Baserow test completed!</fg=green>');
    }
}