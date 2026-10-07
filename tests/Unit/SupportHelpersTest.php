<?php

namespace Tests\Unit;

use App\Support\LogSanitizer;
use App\Support\StatusBadge;
use PHPUnit\Framework\TestCase;

/**
 * TEST-01: fungsi murni di app/Support - tidak ada database, session, atau
 * layanan eksternal. Dua di antaranya relevan untuk keamanan: LogSanitizer
 * (mencegah log poisoning) dan StatusBadge (output di-escape).
 */
final class SupportHelpersTest extends TestCase
{
    // ---------- LogSanitizer (mitigasi log poisoning) ----------

    public function test_log_sanitizer_leaves_a_clean_message_untouched(): void
    {
        $this->assertSame('Connection refused', LogSanitizer::clean('Connection refused'));
    }

    public function test_log_sanitizer_replaces_line_breaks_so_one_entry_stays_one_line(): void
    {
        $this->assertSame('line1 line2', LogSanitizer::clean("line1\nline2"));
        $this->assertSame('line1 line2', LogSanitizer::clean("line1\rline2"));
        $this->assertSame('line1  line2', LogSanitizer::clean("line1\r\nline2"));
    }

    public function test_log_sanitizer_blocks_a_forged_log_line(): void
    {
        // Input yang mencoba menyuntikkan baris log palsu setelah baris asli.
        $malicious = "login failed\n[2026-10-07 10:00:00] INFO admin logged in";

        $cleaned = LogSanitizer::clean($malicious);

        $this->assertStringNotContainsString("\n", $cleaned);
        $this->assertStringNotContainsString("\r", $cleaned);
    }

    // ---------- StatusBadge ----------

    public function test_status_badge_uses_the_color_mapped_to_each_status(): void
    {
        $expected = [
            'Draft' => 'gray',
            'Ordered' => 'amber',
            'PendingApproval' => 'amber',
            'PartiallyReceived' => 'blue',
            'Approved' => 'blue',
            'Received' => 'green',
            'Fulfilled' => 'green',
            'Cancelled' => 'red',
            'Active' => 'green',
            'Inactive' => 'gray',
        ];

        foreach ($expected as $status => $color) {
            $this->assertSame($color, StatusBadge::colorFor($status), "Warna untuk {$status}");
        }
    }

    public function test_status_badge_renders_the_expected_markup(): void
    {
        $this->assertSame(
            '<span class="badge badge-blue">Approved</span>',
            StatusBadge::render('Approved'),
        );
    }

    public function test_status_badge_falls_back_to_gray_for_an_unknown_status(): void
    {
        $this->assertSame('gray', StatusBadge::colorFor('SomethingNew'));
        $this->assertStringContainsString('badge-gray', StatusBadge::render('SomethingNew'));
    }

    public function test_status_badge_escapes_html_in_the_status_text(): void
    {
        $html = StatusBadge::render('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
