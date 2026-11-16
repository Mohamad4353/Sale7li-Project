@extends('errors.minimal')
@section('code', '419')
@section('title', app()->getLocale() === 'ar' ? 'انتهت صلاحية الصفحة' : 'Page expired')
@section('message', app()->getLocale() === 'ar' ? 'انتهت جلستك. يرجى تحديث الصفحة والمحاولة مرة أخرى.' : 'Your session expired. Please refresh and try again.')
