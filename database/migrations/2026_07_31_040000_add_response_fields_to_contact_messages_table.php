<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('contact_messages', 'response')) {
                $table->text('response')->nullable()->after('message');
            }

            if (! Schema::hasColumn('contact_messages', 'answered_at')) {
                $table->timestamp('answered_at')->nullable()->after('response');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            foreach (['response', 'answered_at'] as $column) {
                if (Schema::hasColumn('contact_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
