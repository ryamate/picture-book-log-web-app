import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useBooks } from '../hooks/useBooks';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

const STATUS_TABS = [
  { label: '全て', value: undefined },
  { label: '未読', value: 'unread' },
  { label: '読書中', value: 'reading' },
  { label: '読了', value: 'read' },
] as const;

const STATUS_LABELS: Record<string, string> = {
  unread: '未読',
  reading: '読書中',
  read: '読了',
};

export default function BookshelfPage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const navigate = useNavigate();
  const [status, setStatus] = useState<string | undefined>();
  const [page, setPage] = useState(1);
  const { data, isLoading } = useBooks(familyId, { status, page });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">本棚</h1>
        <Button asChild>
          <Link to="/books/search">絵本を検索</Link>
        </Button>
      </div>

      <div className="flex gap-2">
        {STATUS_TABS.map((tab) => (
          <Button
            key={tab.label}
            variant={status === tab.value ? 'default' : 'outline'}
            size="sm"
            onClick={() => {
              setStatus(tab.value);
              setPage(1);
            }}
          >
            {tab.label}
          </Button>
        ))}
      </div>

      {isLoading && <p className="text-muted-foreground">読み込み中...</p>}

      {data && data.data.length === 0 && (
        <p className="text-muted-foreground">
          まだ絵本が登録されていません。検索して追加しましょう。
        </p>
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {data?.data.map((book) => (
          <Card
            key={book.id}
            className="cursor-pointer transition-shadow hover:shadow-md"
            onClick={() => navigate(`/books/${book.id}`)}
          >
            <CardContent className="flex gap-3 p-4">
              {book.thumbnail_url ? (
                <img
                  src={book.thumbnail_url}
                  alt={book.title}
                  className="h-28 w-20 shrink-0 rounded object-cover"
                />
              ) : (
                <div className="flex h-28 w-20 shrink-0 items-center justify-center rounded bg-muted text-xs text-muted-foreground">
                  No Image
                </div>
              )}
              <div className="min-w-0 flex-1 space-y-1">
                <h3 className="line-clamp-2 text-sm font-medium">{book.title}</h3>
                <p className="text-xs text-muted-foreground">
                  {book.authors.join(', ') || '著者不明'}
                </p>
                <span className="inline-block rounded bg-muted px-2 py-0.5 text-xs">
                  {STATUS_LABELS[book.read_status] ?? book.read_status}
                </span>
                {book.rating && (
                  <p className="text-sm text-yellow-500">{'★'.repeat(book.rating)}</p>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {data && data.meta.last_page > 1 && (
        <div className="flex items-center justify-center gap-2">
          <Button
            variant="outline"
            size="sm"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
          >
            前へ
          </Button>
          <span className="text-sm text-muted-foreground">
            {data.meta.current_page} / {data.meta.last_page}
          </span>
          <Button
            variant="outline"
            size="sm"
            disabled={page >= data.meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            次へ
          </Button>
        </div>
      )}
    </div>
  );
}
