<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_regulatory_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('document_type', 20);
            $table->string('document_number');
            $table->date('document_date');
            $table->string('document_form', 80);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'document_number'], 'prd_type_number_unique');
            $table->index(['product_id', 'document_type'], 'prd_product_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_regulatory_documents');
    }
};
