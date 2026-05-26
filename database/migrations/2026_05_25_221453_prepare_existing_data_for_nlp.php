<?php

use App\Models\Report;
use App\Models\Sharing;
use App\Models\User;
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
        Schema::table('sharings',function(Blueprint $table){
            $table->dateTime('cutdown_for_report')->nullable();
            $table->enum('priority', ['tinggi', 'rendah','sedang'])->default('rendah')->change();
        });
        Schema::table('reports',function(Blueprint $table){
            $table->dateTime('cutdown_for_report')->nullable();
            $table->enum('priority', ['tinggi', 'rendah','sedang'])->default('rendah')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Sharing::wherePriority('sedang')->update(['priority'=>'rendah']);
        Report::wherePriority('sedang')->update(['priority'=>'rendah']);
        Schema::table('sharings',function(Blueprint $table){
            $table->dropColumn('cutdown_for_report');
            $table->enum('priority', ['tinggi', 'rendah'])->default('rendah')->change();
        });
        Schema::table('reports',function(Blueprint $table){
            $table->dropColumn('cutdown_for_report');
            $table->enum('priority', ['tinggi', 'rendah'])->default('rendah')->change();
        });
    }
};
