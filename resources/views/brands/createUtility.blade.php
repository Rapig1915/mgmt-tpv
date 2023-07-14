@extends('layouts.app')

@section('title')
Add Utility
@endsection

@section('content')
<div id="create-brand-utility">
    <create-brand-utility 
        :all-utilities="{{ json_encode($all_utilities) }}"
        :brand="{{ json_encode($brand) }}"
    />
</div>
@endsection