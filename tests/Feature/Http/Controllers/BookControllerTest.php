<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('book search matches title, author, and genre', function () {
    $genre = Genre::create([
        'name' => 'Fantasy',
        'description' => 'Fantasy books',
    ]);
    $author = Author::create([
        'name' => 'N. K. Jemisin',
        'photo' => 'authors/jemisin.jpg',
        'bio' => 'Author biography',
    ]);
    $book = Book::create([
        'title' => 'The Solar Path',
        'description' => 'A story',
        'price' => 25,
        'stock' => 5,
        'cover_photo' => 'books/solar.jpg',
        'genre_id' => $genre->id,
        'author_id' => $author->id,
    ]);

    $otherGenre = Genre::create([
        'name' => 'Mystery',
        'description' => 'Mystery books',
    ]);
    $otherAuthor = Author::create([
        'name' => 'Another Author',
        'photo' => 'authors/another.jpg',
        'bio' => 'Another biography',
    ]);
    Book::create([
        'title' => 'The Quiet House',
        'description' => 'Another story',
        'price' => 30,
        'stock' => 3,
        'cover_photo' => 'books/quiet.jpg',
        'genre_id' => $otherGenre->id,
        'author_id' => $otherAuthor->id,
    ]);

    foreach (['Solar', 'Jemisin', 'Fantasy'] as $term) {
        $this->getJson('/api/books/search?search='.$term)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $book->id);
    }
});

test('book search requires a search term', function () {
    $this->getJson('/api/books/search')
        ->assertUnprocessable()
        ->assertJsonPath('data.search.0', 'The search field is required.');
});

test('book listing returns every book when search is omitted', function () {
    $genre = Genre::create([
        'name' => 'Fiction',
        'description' => 'Fiction books',
    ]);
    $author = Author::create([
        'name' => 'Sample Author',
        'photo' => 'authors/sample.jpg',
        'bio' => 'Author biography',
    ]);

    foreach (['First Book', 'Second Book'] as $title) {
        Book::create([
            'title' => $title,
            'description' => 'A story',
            'price' => 20,
            'stock' => 4,
            'cover_photo' => 'books/sample.jpg',
            'genre_id' => $genre->id,
            'author_id' => $author->id,
        ]);
    }

    $this->getJson('/api/books')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
