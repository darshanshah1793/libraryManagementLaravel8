<?php

namespace App\Http\Controllers;

use App\Models\Books;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class BooksController extends Controller
{

    public function __construct()
    {
    }

    public function index()
    {
        $books = Books::all();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $books
        ], 200);
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
        $data = $request->only('book_name', 'author', 'cover_image');
        $validator = Validator::make($data, [
            'book_name' => 'required|string',
            'author' => 'required',
            'cover_image' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $book = Books::create([
            'book_name' => $request->book_name,
            'author' => $request->author,
            'cover_image' => $request->cover_image
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book created successfully',
            'data' => $book
        ], 201);
    }

    public function show($id)
    {
        $book = Books::find($id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $book
        ], 200);
    }

    public function edit(Books $book)
    {
        //
    }

    public function update(Request $request, $book)
    {
        $data = $request->only('book_name', 'author', 'cover_image');
        $validator = Validator::make($data, [
            'book_name' => 'required|string',
            'author' => 'required',
            'cover_image' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $checkIfExists = Books::where('id', $book)->get();

        if(count($checkIfExists) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found',
                'id' => $book
            ], 404);
        }

        $updateBook = Books::where('id', $book)->update([
            'book_name' => $request->book_name,
            'author' => $request->author,
            'cover_image' => $request->cover_image
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book updated successfully',
            'data' => $updateBook
        ], 201);
    }

    public function destroy($book)
    {
        $checkIfExists = Books::where('id', $book)->get();

        if(count($checkIfExists) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found',
                'id' => $book
            ], 404);
        }

        $deleteBook = Books::where('id', $book)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully',
            'data' => $deleteBook
        ], 200);
    }
}
