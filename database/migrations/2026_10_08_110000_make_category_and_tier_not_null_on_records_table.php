<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        // fix existing records
        $firstCategoryId = DB::table('categories')->orderBy('id')->value('id');

        if ($firstCategoryId !== null) {
            DB::table('records')
                ->whereNull('category_id')
                ->update(['category_id' => $firstCategoryId]);
        }

        $firstTierId = DB::table('tiers')->orderBy('id')->value('id');

        if ($firstTierId !== null) {
            DB::table('records')
                ->whereNull('tier_id')
                ->update(['tier_id' => $firstTierId]);
        }

        Schema::table('records', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->foreignId('tier_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
            $table->foreignId('tier_id')->nullable()->change();
        });
    }
};
