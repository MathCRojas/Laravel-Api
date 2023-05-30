<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authors extends Model
{
    use HasFactory;

    protected $table = "authors";

    protected $fillable = [
        "id",
        "name",
        "first_surname",
        "second_surname"
    ];

    public $timestamps = false;

    public function books(){
        return $this->hasOne(
            Book::class, //Table relationship
            'authors_books', //Table private o intersection
            "authors_id", //from
            "books_id" //to
        );
    }
}
