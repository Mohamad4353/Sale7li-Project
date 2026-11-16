@extends('errors.minimal')
@section('code', '500')
@section('title', app()->getLocale() === 'ar' ? 'حدث خطأ ما' : 'Something went wrong')
@section('message', app()->getLocale() === 'ar' ? 'حدث خطأ غير متوقع لدينا. يرجى المحاولة لاحقًا.' : 'An unexpected error occurred on our side. Please try again later.')
