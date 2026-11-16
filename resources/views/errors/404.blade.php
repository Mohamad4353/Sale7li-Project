@extends('errors.minimal')
@section('code', '404')
@section('title', app()->getLocale() === 'ar' ? 'الصفحة غير موجودة' : 'Page not found')
@section('message', app()->getLocale() === 'ar' ? 'تعذر العثور على الصفحة التي تبحث عنها.' : 'The page you are looking for could not be found.')
