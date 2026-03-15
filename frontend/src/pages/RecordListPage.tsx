import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useRecords } from '../hooks/useRecords';
import { useChildren } from '../hooks/useChildren';
import { useBooks } from '../hooks/useBooks';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import EmptyState from '@/components/EmptyState';

export default function RecordListPage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const navigate = useNavigate();

  const [childId, setChildId] = useState<number | undefined>();
  const [pictureBookId, setPictureBookId] = useState<number | undefined>();
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [page, setPage] = useState(1);

  const { data: childrenData } = useChildren(familyId);
  const { data: booksData } = useBooks(familyId);

  const { data, isLoading } = useRecords(familyId, {
    child_id: childId,
    picture_book_id: pictureBookId,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
    page,
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">読み聞かせ記録</h1>
        <Button onClick={() => navigate('/records/new')}>
          読み聞かせを記録する
        </Button>
      </div>

      <div className="space-y-3 rounded border p-4">
        <h2 className="text-sm font-medium">絞り込み</h2>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label className="mb-1 block text-xs text-muted-foreground">お子さま</label>
            <select
              className="w-full rounded border px-2 py-1.5 text-sm"
              value={childId ?? ''}
              onChange={(e) => {
                setChildId(e.target.value ? Number(e.target.value) : undefined);
                setPage(1);
              }}
            >
              <option value="">すべて</option>
              {childrenData?.map((child) => (
                <option key={child.id} value={child.id}>
                  {child.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs text-muted-foreground">絵本</label>
            <select
              className="w-full rounded border px-2 py-1.5 text-sm"
              value={pictureBookId ?? ''}
              onChange={(e) => {
                setPictureBookId(e.target.value ? Number(e.target.value) : undefined);
                setPage(1);
              }}
            >
              <option value="">すべて</option>
              {booksData?.data.map((book) => (
                <option key={book.id} value={book.id}>
                  {book.title}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs text-muted-foreground">開始日</label>
            <input
              type="date"
              className="w-full rounded border px-2 py-1.5 text-sm"
              value={dateFrom}
              onChange={(e) => {
                setDateFrom(e.target.value);
                setPage(1);
              }}
            />
          </div>
          <div>
            <label className="mb-1 block text-xs text-muted-foreground">終了日</label>
            <input
              type="date"
              className="w-full rounded border px-2 py-1.5 text-sm"
              value={dateTo}
              onChange={(e) => {
                setDateTo(e.target.value);
                setPage(1);
              }}
            />
          </div>
        </div>
      </div>

      {isLoading && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <Card key={i}>
              <CardContent className="flex gap-3 p-4">
                <Skeleton className="h-28 w-20 shrink-0 rounded" />
                <div className="min-w-0 flex-1 space-y-2">
                  <Skeleton className="h-4 w-4/5" />
                  <Skeleton className="h-3.5 w-2/5" />
                  <Skeleton className="h-3 w-3/5" />
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {data && data.data.length === 0 && (
        (childId || pictureBookId || dateFrom || dateTo) ? (
          <EmptyState
            message="条件に一致する記録がありません"
            actionLabel="フィルターをクリア"
            onAction={() => {
              setChildId(undefined);
              setPictureBookId(undefined);
              setDateFrom('');
              setDateTo('');
              setPage(1);
            }}
          />
        ) : (
          <EmptyState
            message="まだ読み聞かせの記録がありません"
            actionLabel="記録をつける"
            onAction={() => navigate('/records/new')}
          />
        )
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {data?.data.map((record) => (
          <Card
            key={record.id}
            className="cursor-pointer transition-shadow hover:shadow-md"
            onClick={() => navigate(`/records/${record.id}`)}
          >
            <CardContent className="flex gap-3 p-4">
              {record.picture_book.thumbnail_url ? (
                <img
                  src={record.picture_book.thumbnail_url}
                  alt={record.picture_book.title}
                  className="h-28 w-20 shrink-0 rounded object-cover"
                />
              ) : (
                <div className="flex h-28 w-20 shrink-0 items-center justify-center rounded bg-muted text-xs text-muted-foreground">
                  No Image
                </div>
              )}
              <div className="min-w-0 flex-1 space-y-1">
                <h3 className="line-clamp-2 text-sm font-medium">
                  {record.picture_book.title}
                </h3>
                <p className="text-xs text-muted-foreground">{record.read_date}</p>
                {record.children.length > 0 && (
                  <p className="text-xs text-muted-foreground">
                    {record.children
                      .map((c) => (c.reaction ? `${c.name}(${c.reaction})` : c.name))
                      .join(', ')}
                  </p>
                )}
                {record.tags.length > 0 && (
                  <div className="flex flex-wrap gap-1">
                    {record.tags.map((tag) => (
                      <span
                        key={tag.id}
                        className="inline-block rounded bg-muted px-1.5 py-0.5 text-xs"
                      >
                        {tag.name}
                      </span>
                    ))}
                  </div>
                )}
                {record.memo && (
                  <p className="line-clamp-2 text-xs text-muted-foreground">{record.memo}</p>
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
