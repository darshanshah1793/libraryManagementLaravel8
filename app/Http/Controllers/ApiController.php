<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Books;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function register(Request $request)
    {
        //Validate data
        $data = $request->only('name', 'email', 'password');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        //User created, return success response
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is validated
        //Crean token
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            }
        } catch (JWTException $e) {
            return $credentials;
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
            ], 500);
        }

        //Token created, return with success response and jwt token
        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_user(Request $request)
    {
        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }

    public function rent_book(Request $request)
    {

        $user = JWTAuth::authenticate($request->token);

        $data = $request->only('book_id', 'status');
        $validator = Validator::make($data, [
            'book_id' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $checkIfExists = Books::where('id', $request->book_id)->get();

        if (count($checkIfExists) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found',
                'id' => $request->book_id
            ], 404);
        }

        $message = "";
        if ($request->status == 1) {
            $checkIfRentedBookExists = DB::table('rented_books')->whereRaw('user_id=? and book_id=? and status=?', [$user->id, $request->book_id, 1])->get();

            if (count($checkIfRentedBookExists) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book already rented',
                    'id' => $request->book_id
                ], 200);
            }

            $rentBook = DB::insert('insert into rented_books (user_id, book_id, status, created_at, updated_at) values (?, ?, ?, ?, ?)', [$user->id, $request->book_id, 1, date('Y=m-d H:i:s', strtotime('now')), date('Y=m-d H:i:s', strtotime('now'))]);
            $message = "Book rented successfully";
        } else {
            $checkIfRentedBookExists = DB::table('rented_books')->whereRaw('user_id=? and book_id=? and status=?', [$user->id, $request->book_id, 1])->get();

            if (count($checkIfRentedBookExists) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rented book not found',
                    'id' => $request->book_id
                ], 404);
            }

            $rentBook = DB::table('rented_books')->where('book_id', $request->book_id)->update(['status' => 2, 'updated_at' => date('Y=m-d H:i:s', strtotime('now'))]);
            $message = "Book returned successfully";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $rentBook
        ], 201);
    }

    public function getUserRentedBooks()
    {
        // SELECT rented_books.user_id, users.name, GROUP_CONCAT(books.book_name) as rentedBooks FROM `rented_books` JOIN users on users.id = rented_books.user_id JOIN books on books.id = rented_books.book_id WHERE rented_books.status = 1 GROUP BY rented_books.user_id;
        $getUserwiseRentedBooks = DB::table('rented_books')->selectRaw('rented_books.user_id, users.name, GROUP_CONCAT(books.book_name) as rentedBooks')->join('users', 'users.id', '=', 'rented_books.user_id')->join('books', 'books.id', '=', 'rented_books.book_id')->whereRaw('rented_books.status = 1')->groupBy('rented_books.user_id')->get();

        if(count($getUserwiseRentedBooks) > 0) {
            $finalData = [];
            foreach($getUserwiseRentedBooks as $userRentedBooks) {
                $obj = (object)[];
                $obj->user_name = $userRentedBooks->name;
                $obj->rented_books = $userRentedBooks->rentedBooks;

                $finalData[] = $obj;
            }

            return response()->json([
                'success' => true,
                'message' => '',
                'data' => $finalData
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No data found',
                'data' => $getUserwiseRentedBooks
            ], 200);
        }
    }
}
