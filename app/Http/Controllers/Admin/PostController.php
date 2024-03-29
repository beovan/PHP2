<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Services\Post\PostService;
class PostController extends Controller
{
    protected $post;

    public function __construct(PostService $post)
    {
        $this->post = $post;
    }
    
    public function create()
    {
        return view('admin.post.add', [
            'title' => 'Thêm bài viết mới'
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|max:255|min:5',
            'content' => 'required',
            'thumb' => 'required',
        ]);

        $this->post->insert($request);

        return redirect()->back();
    }

    public function index(Request $request)
    {
        $search_param = $request->query('q');
        $posts = $this->post->get($search_param);
        
        return view('admin.post.list', [
            'title' => 'Danh Sách bài viết Mới Nhất',
            'posts' => $posts,
            'search_param'=> $search_param
        ]);
    }

    public function show(Post $post)
    {
        return view('admin.post.edit', [
            'title' => 'Chỉnh Sửa bài viết',
            'post' => $post
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $this->validate($request, [
            'title' => 'required|max:255',
            'content' => 'required',
            'thumb' => 'required',
        ]);

        $result = $this->post->update($request, $post);
        if ($result) {
            return redirect('/admin/posts/list');
        }

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $result = $this->post->destroy($request);
        if ($result) {
            return response()->json([
                'error' => false,
                'message' => 'Xóa thành công bài viết'
            ]);
        }

        return response()->json([ 'error' => true ]);
    }
}
