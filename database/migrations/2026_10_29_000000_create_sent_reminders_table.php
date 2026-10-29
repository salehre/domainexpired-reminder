<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The table may already exist if database/sql/sent_reminders.sql was imported by hand.
        if (Schema::hasTable('sent_reminders')) {
            return;
        }

        Schema::create('sent_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 20);          // domain | hosting
            $table->unsignedBigInteger('item_id');    // tbldomains.id / tblhosting.id
            $table->date('expires_on');               // the date that was reported
            $table->string('name')->nullable();       // domain name, just to be readable in phpMyAdmin
            $table->unsignedBigInteger('user_id')->nullable(); // WHMCS client id
            $table->timestamp('sent_at')->useCurrent();

            // A renewal changes expires_on, so it produces a new row => a new reminder.
            $table->unique(['item_type', 'item_id', 'expires_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_reminders');
    }
};
