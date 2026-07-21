@extends('errors.layout')

@section('title', 'Erreur serveur')
@section('code', '500')
@section('headline', 'Oups… une erreur est survenue')
@section('message')
    Nous avons rencontré un problème technique côté serveur. Notre équipe peut le corriger rapidement.
    Veuillez rafraîchir la page ou revenir à l’accueil.
@endsection

