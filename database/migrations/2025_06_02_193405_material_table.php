<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $fillable = [
        'user_id',
        'title',
        'semester',
        'unit',
        'file_path',
        'rating'
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('semester');
            $table->string('unit');
            $table->string('file_path'); // File path
            $table->tinyInteger('rating')->default(0); // 0-5
            $table->timestamps(); // created_at and updated_at
            $table->index(['semester', 'unit']); // Index for frequent queries 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('material');
    }
};
