@extends('errors.minimal')
@section('code', '503')
@section('title', app()->getLocale() === 'ar' ? 'سنعود قريبًا' : 'Be right back')
@section('message', app()->getLocale() === 'ar' ? 'SALE7LI غير متاح مؤقتًا للصيانة.' : 'SALE7LI is temporarily unavailable for maintenance.')
