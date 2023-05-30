<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReview;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon\Date;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{

    public function BookController()
    {
    }

    public function index()
    {
        // $books = Book::all();
        $books = Book::with('authors', 'category', 'editorial')->get();
        return [
            "error" => false,
            "message" => "Successfull query",
            "data" => $books
        ];
    }

    public function show($id)
    {
        $response = $this->getReponse();
        $book = Book::with('category', 'editorial', 'authors')->where("id", $id)->get();
        if ($book) {
            $response["error"] = false;
            $response["message"] = "Successfull query!";
            $response["data"] = $book;
        } else {
            $response["message"] = "Not found";
        }
        return $response;
    }

    public function delete($id)
    {
        $response = $this->getResponse();
        $book = Book::find($id);

        if ($book) {
            foreach ($book->authors as $item) {
                $book->authors()->detach($item->id);
            }
            $book->delete();
            $response["error"] = false;
            $response["message"] = "Your book has been removed!";
            $response["data"] = $book;
        } else {
            $response["message"] = "Not found";
        }
        return $response;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $existIsbn = Book::where("isbn", trim($request->isbn))->exists();

            if (!$existIsbn) {
                $book = new Book();
                $book->isbn = trim($request->isbn);
                $book->title = trim($request->title);
                $book->description = trim($request->description);
                $book->category_id = $request->category['id'];
                $book->editorial_id = $request->editorial['id'];
                $book->publish_date = Carbon::now();
                $book->save();

                foreach ($request->authors as $item) {
                    $book->authors()->attach($item);
                }

                $bookId = $book->id;
                return [
                    "status" => true,
                    "message" => "Your book has been created!",
                    "data" => [
                        "book_id" => $bookId,
                        "book" => $book
                    ]
                ];
            } else {
                return [
                    "error" => true,
                    "message" => "The isbn already exist",
                    "data" => []
                ];
            }

            DB::commit(); //Save all
        } catch (Exception $e) {
            DB::rollBack(); //Revert all
            return [
                "error" => true,
                "message" => "Wrong operation!",
                "data" => []
            ];
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $response = $this->getResponse();
            $book = Book::find($id);

            if ($book) {

                $isbnOwner = Book::where("isbn", $request->isbn)->first();

                if (!$isbnOwner || $isbnOwner->id == $book->id) {

                    $book->isbn = trim($request->isbn);
                    $book->title = trim($request->title);
                    $book->description = trim($request->description);
                    $book->category_id = $request->category['id'];
                    $book->editorial_id = $request->editorial['id'];
                    $book->publish_date = Carbon::now();
                    $book->update();

                    //Delete old authors
                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item->id);
                    }

                    //Add new authors
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }

                    $book = Book::with('authors', 'category', 'editorial')->where("id", $id)->get();
                    $response["data"] = $book;
                    $response["error"] = false;
                    $response["message"] = "Your bok has been updated!";
                } else {
                    $response["message"] = "ISBN DUPLICATED";
                }
            } else {

                $response["error"] = true;
                $response["message"] = "NOT FOUND";
            }

            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack(); //Revert all
            return [
                "error" => true,
                "message" => "Wrong operation!",
                "data" => $e
            ];
        }
    }

    public function addBookReview(Request $request)
    {
        DB::beginTransaction();

        try {
            $review = new BookReview();
            $review->comment = $request->comment;
            $review->edited = false;
            $review->book_id = $request->book_id;
            $review->user_id = auth()->user()->id;
            $review->save();
            DB::commit(); //Save all

            return [
                "status" => true,
                "message" => "Your review has been created!",
                "data" => [
                    "book_review" => $review
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack(); //Revert all
            return [
                "error" => false,
                "message" => "Wrong operation!",
                "data" => $review
            ];
        }
    }

    public function updateBookReview(Request $request, $id)
    {
        $userAuth = auth()->user();
        if (isset($userAuth)) {
            $review = BookReview::find($id);
            if ($review && $review->user_id == $userAuth->id) {
                $review->comment = $request->comment;
                $review->edited = true;
                $review->user_id = auth()->user()->id;
                $review->update();

                return $this->getResponse201('review', 'updated', $review);

            } else {
                return $this->getResponse403();
            }
        }else{
            return $this->getResponse401();
        }
    }
}
