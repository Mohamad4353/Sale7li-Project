@extends('errors.minimal')
@section('code', '429')
@section('title', app()->getLocale() === 'ar' ? 'طلبات كثيرة جدًا' : 'Too many requests')
@section('message', app()->getLocale() === 'ar' ? 'لقد أرسلت طلبات كثيرة. يرجى الانتظار قليلاً ثم المحاولة مجددًا.' : 'You have made too many requests. Please wait a moment and try again.')
