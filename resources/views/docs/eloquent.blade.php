@extends('docs.main')

@section('content')

<h3>Eloquent</h3>
<hr>
<h5>Eloquent has a lot of features, only the basic ones are implemented</h5>

<br><b>Retrieving models:</b>
<pre><code class="language-php7">foreach (Flight::all() as $flight) {
    echo $flight->name;
}</code></pre>
<br><b>Building queries:</b>
<pre><code class="language-php7">$flights = Flight::where('active', 1)
               ->orderBy('name')
               ->take(10)
               ->get();
</code></pre>

<br><b>Creating a new model:</b>
<pre><code class="language-php7">$flight = new Flight;
$flight->name = $request->name;
$flight->save();
</code></pre>

<br><b>Updating models:</b>
<pre><code class="language-php7">Flight::where('active', 1)
      ->where('destination', 'San Diego')
      ->update(array('delayed' => 1));
</code></pre>
<br>
<h4># Relationships</h4>
<h5>Works the same way (but not all features are implemented)</h5>
<p>Available relationships: <br><b>hasOne - hasMany - belongsTo - hasOneThrough - hasManyThrough - belongsToMany - morphOne - morphMany - morphTo - morphToMany - morphedByMany</b></p>
<br><b>Making relationships:</b>
<pre><code class="language-php7">public function playlists()
{
    return $this->hasMany('Playlist', 'user_id', 'id');
}

public function songs()
{
    return $this->hasManyThrough('PlaylistContent', 'playlists', 'user_id', 'playlist_id', 'id', 'pid');
}
</code></pre>
<br><b>Using annonymous functions:</b>
<pre><code class="language-php7">Product::where( function($query) {
    $query->where('id', 1);
    $query->orWhere( function($query) {
        $query->where('description', 'shirt');
        $query->whereIn('statues', [1, 2, 3]);
    });
})->get();

Product::join('categories', function ($join) {
    $join->on('categories.id', '=', 'products.category_id')
         ->where('categories.id', '>', 1);
})->where('id', '<', 2200)->get();
</code></pre>

<br><b>Nested relations:</b>
<pre><code class="language-php7">Music::with('playlists.songs')->get();
</code></pre>

<br><b>Scopes:</b>
<pre><code class="language-php7">public function scopeWithWhereHas($query, $relation, $constraint=null)
{
    return $query->whereHas($relation, $constraint)
        ->with(array($relation => $constraint));
}
</code></pre><br>

@endsection
