import { useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useSearchGoogleBooks, useAddBook } from '../hooks/useBooks';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import EmptyState from '@/components/EmptyState';
import type { GoogleBook } from '../api/books';
import { useNavigate } from 'react-router-dom';

export default function BookSearchPage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const [query, setQuery] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const { data, isLoading } = useSearchGoogleBooks(searchQuery);
  const navigate = useNavigate();
  const addBook = useAddBook(familyId);
  const [addedIds, setAddedIds] = useState<Set<string>>(new Set());

  const handleSearch = () => {
    const trimmed = query.trim();
    if (trimmed.length >= 2) {
      setSearchQuery(trimmed);
    }
  };

  const handleAdd = async (book: GoogleBook) => {
    try {
      await addBook.mutateAsync({
        google_books_id: book.google_books_id,
        isbn: book.isbn ?? undefined,
        title: book.title,
        authors: book.authors,
        thumbnail_url: book.thumbnail_url ?? undefined,
      });
      setAddedIds((prev) => new Set(prev).add(book.google_books_id));
    } catch {
      // 409 conflict = already registered
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">絵本を検索</h1>
        <Button variant="outline" onClick={() => navigate('/books')}>
          本棚に戻る
        </Button>
      </div>

      <form
        className="flex gap-2"
        onSubmit={(e) => {
          e.preventDefault();
          handleSearch();
        }}
      >
        <Input
          placeholder="タイトルや著者名で検索..."
          value={query}
          onChange={(e) => setQuery(e.target.value)}
        />
        <Button type="submit" disabled={query.trim().length < 2 || isLoading}>
          検索
        </Button>
      </form>

      {isLoading && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <Card key={i}>
              <CardContent className="flex gap-4 p-4">
                <Skeleton className="h-32 w-24 shrink-0 rounded" />
                <div className="min-w-0 flex-1 space-y-2">
                  <Skeleton className="h-4 w-4/5" />
                  <Skeleton className="h-3.5 w-3/5" />
                  <Skeleton className="h-3 w-1/4" />
                  <Skeleton className="h-7 w-24" />
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {data && data.items.length === 0 && searchQuery.length >= 2 && (
        <EmptyState message="検索結果が見つかりませんでした" />
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {data?.items.map((book) => (
          <Card key={book.google_books_id}>
            <CardContent className="flex gap-4 p-4">
              {book.thumbnail_url ? (
                <img
                  src={book.thumbnail_url}
                  alt={book.title}
                  className="h-32 w-24 shrink-0 rounded object-cover"
                />
              ) : (
                <div className="flex h-32 w-24 shrink-0 items-center justify-center rounded bg-muted text-xs text-muted-foreground">
                  No Image
                </div>
              )}
              <div className="min-w-0 flex-1 space-y-1">
                <h3 className="line-clamp-2 font-medium">{book.title}</h3>
                <p className="text-sm text-muted-foreground">
                  {book.authors.join(', ') || '著者不明'}
                </p>
                {book.published_date && (
                  <p className="text-xs text-muted-foreground">{book.published_date}</p>
                )}
                <Button
                  size="sm"
                  disabled={addedIds.has(book.google_books_id) || addBook.isPending}
                  onClick={() => handleAdd(book)}
                >
                  {addedIds.has(book.google_books_id) ? '登録済み' : '本棚に追加'}
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
