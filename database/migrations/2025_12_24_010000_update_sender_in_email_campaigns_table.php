<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            // This migration assumes that the existing `sender` column (varchar)
            // will be manually converted to user IDs before running this migration,
            // or that the table can be cleared. A direct type change will fail
            // if non-numeric data (like email addresses) exists.
            //
            // For safety, we will drop the old column and add a new one.
            // WARNING: This will delete all data in the 'sender' column.
            $table->dropColumn('sender');
        });

        Schema::table('email_campaigns', function (Blueprint $table) {
            // Add the new sender column as a foreign key to users
            $table->foreignId('sender')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropForeign(['sender']);
            $table->dropColumn('sender');
        });

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->string('sender');
        });
    }
};
