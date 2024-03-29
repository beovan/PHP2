@extends('admin.main')

@section('head')
<script src="/ckeditor/ckeditor.js"></script>
@endsection

@section('content')
<form action="" method="POST">
    <div class="card-body">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="menu">Tiêu đề</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control"  placeholder="Nhập tiêu đề">
                </div>
            </div>
        <div class="form-group">
            <label>Mô Tả Chi Tiết</label>
            <textarea name="content" id="content" class="form-control">{{ old('content') }}</textarea>
        </div>
        <div class="form-group">
            <label for="menu" >Ảnh bài viết</label>
            <input type="file" class="form-control" id="upload">
            <div id="image_show">
            </div>
            <input type="hidden" name="thumb" id="thumb">
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Thêm bài viết</button>
    </div>
    @csrf
</form>
@endsection

@section('footer')
    <script>
        CKEDITOR.replace( 'content' );
        CKEDITOR.config.imageResize.maxWidth = 800;
        CKEDITOR.config.imageResize.maxHeight = 800;
    </script>

@endsection
