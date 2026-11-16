@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('heading', 'Please slow down')
@section('message', 'Too many requests in a short time. Please wait a minute and try again.')