<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * index
     *
     * @return void 
     */
    public function index()
    {
        // get all posts
        $posts = Post::latest()->paginate(5);
        // return collection of posts as a resource
        return new PostResource(true, 'List Data Posts', $posts);
    }

    /**
     * store
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        // define validation rules
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'required',
            'content' => 'required',
        ]);

        // check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // upload image
        $image = $request->file('image');
        $image->storeAs('public/posts', $image->hashName());

        // create post
        $post = Post::create([
            'image' => $image->hashName(),
            'title' => $request->title,
            'content' => $request->content,
        ]);

        // return response
        return new PostResource(true, 'Data Post Berhasil Ditambahkan!', $post);
    }

    /**
     * show
     *
     * @param int $id
     * @return void
     */
    public function show($id)
    {
        $post = Post::find($id);

        // check if post is not found
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return new PostResource(true, 'Detail Data Post!', $post);
    }

    /**
    * update
    *
    * @param Request $request
    * @param int $id
    * @return void
    */
    public function update(Request $request, $id)
    {
        // Cari post berdasarkan ID
        $post = Post::find($id);

        // Jika post tidak ditemukan, kembalikan response error
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        // Jika validasi gagal, kembalikan response error
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update post data
        $post->title = $request->title;
        $post->content = $request->content;

        // Jika ada file gambar yang diupload, proses pembaruan gambar
        if ($request->hasFile('image')) {
            // Validasi file gambar
            $imageValidator = Validator::make($request->all(), [
                'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            // Jika validasi gambar gagal, kembalikan response error
            if ($imageValidator->fails()) {
                return response()->json($imageValidator->errors(), 422);
            }

            // Hapus gambar lama dari penyimpanan
            Storage::delete('public/posts/' . $post->image);

            // Upload gambar baru
            $image = $request->file('image');
            $image->storeAs('public/posts', $image->hashName());

            // Update nama file gambar di database
            $post->image = $image->hashName();
        }

        // Simpan perubahan
        $post->save();

        // Kembalikan response berhasil diupdate
        return new PostResource(true, 'Data Post Berhasil Diupdate!', $post);
    }

    /**
     * destroy
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        // Cari post berdasarkan ID
        $post = Post::find($id);

        // Jika post tidak ditemukan, kembalikan response error
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Hapus gambar terkait dari penyimpanan
        Storage::delete('public/posts/' . $post->image);

        // Hapus post dari database
        $post->delete();

        // Kembalikan response berhasil dihapus
        return response()->json(['message' => 'Post deleted successfully']);
    }


}