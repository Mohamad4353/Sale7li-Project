@extends('errors.minimal')
@section('code', '403')
@section('title', app()->getLocale() === 'ar' ? 'غير مسموح' : 'Forbidden')
@section('message', app()->getLocale() === 'ar' ? 'ليس لديك إذن للوصول إلى هذه الصفحة.' : 'You do not have permission to access this page.')
