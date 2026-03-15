import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useBook, useUpdateBook, useRemoveBook } from '../hooks/useBooks';
import { useRecords } from '../hooks/useRecords';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

const STATUS_OPTIONS = [
  { label: '未読', value: 'unread' },
  { label: '読書中', value: 'reading' },
  { label: '読了', value: 'read' },
] as const;

export default function BookDetailPage() {
  const { bookId } = useParams<{ bookId: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const numericBookId = Number(bookId);

  const { data: book, isLoading } = useBook(familyId, numericBookId);
  const updateBook = useUpdateBook(familyId, numericBookId);
  const removeBook = useRemoveBook(familyId);
  const { data: recordsData } = useRecords(familyId, { picture_book_id: numericBookId, per_page: 5 });

  const [isEditing, setIsEditing] = useState(false);
  const [rating, setRating] = useState<number | null>(null);
  const [readStatus, setReadStatus] = useState('unread');
  const [review, setReview] = useState('');

  const startEditing = () => {
    if (book) {
      setRating(book.rating);
      setReadStatus(book.read_status);
      setReview(book.review ?? '');
      setIsEditing(true);
    }
  };

  const handleSave = async () => {
    await updateBook.mutateAsync({
      rating,
      read_status: readStatus,
      review: review || null,
    });
    setIsEditing(false);
  };

  const handleDelete = async () => {
    if (window.confirm('この絵本を本棚から削除しますか？')) {
      await removeBook.mutateAsync(numericBookId);
      navigate('/books');
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-24" />
        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-3/5" />
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex flex-col gap-6 md:flex-row">
              <Skeleton className="h-48 w-36 shrink-0 rounded" />
              <div className="flex-1 space-y-2">
                <Skeleton className="h-4 w-2/3" />
                <Skeleton className="h-4 w-1/3" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!book) {
    return <p className="text-muted-foreground">絵本が見つかりませんでした</p>;
  }

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => navigate('/books')}>
        本棚に戻る
      </Button>

      <Card>
        <CardHeader>
          <CardTitle>{book.title}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col gap-6 md:flex-row">
            {book.thumbnail_url ? (
              <img
                src={book.thumbnail_url}
                alt={book.title}
                className="h-48 w-36 shrink-0 rounded object-cover"
              />
            ) : (
              <div className="flex h-48 w-36 shrink-0 items-center justify-center rounded bg-muted text-sm text-muted-foreground">
                No Image
              </div>
            )}
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground">
                著者: {book.authors.join(', ') || '不明'}
              </p>
              {book.isbn && (
                <p className="text-sm text-muted-foreground">ISBN: {book.isbn}</p>
              )}
              {book.registered_by && (
                <p className="text-sm text-muted-foreground">
                  登録者: {book.registered_by.name}
                </p>
              )}
            </div>
          </div>

          {isEditing ? (
            <div className="space-y-4 rounded border p-4">
              <div>
                <label className="mb-1 block text-sm font-medium">評価</label>
                <div className="flex gap-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      type="button"
                      className={`text-2xl ${
                        rating !== null && star <= rating
                          ? 'text-yellow-500'
                          : 'text-gray-300'
                      }`}
                      onClick={() => setRating(star === rating ? null : star)}
                    >
                      ★
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">読書ステータス</label>
                <div className="flex gap-2">
                  {STATUS_OPTIONS.map((opt) => (
                    <Button
                      key={opt.value}
                      variant={readStatus === opt.value ? 'default' : 'outline'}
                      size="sm"
                      onClick={() => setReadStatus(opt.value)}
                    >
                      {opt.label}
                    </Button>
                  ))}
                </div>
              </div>

              <div>
                <label className="mb-1 block text-sm font-medium">レビュー</label>
                <textarea
                  className="w-full rounded border p-2 text-sm"
                  rows={4}
                  value={review}
                  onChange={(e) => setReview(e.target.value)}
                  placeholder="感想を書いてみましょう..."
                />
              </div>

              <div className="flex gap-2">
                <Button onClick={handleSave} disabled={updateBook.isPending}>
                  保存
                </Button>
                <Button variant="outline" onClick={() => setIsEditing(false)}>
                  キャンセル
                </Button>
              </div>
            </div>
          ) : (
            <div className="space-y-2">
              <div className="flex items-center gap-4">
                <span className="inline-block rounded bg-muted px-2 py-0.5 text-sm">
                  {STATUS_OPTIONS.find((o) => o.value === book.read_status)?.label ??
                    book.read_status}
                </span>
                {book.rating && (
                  <span className="text-yellow-500">{'★'.repeat(book.rating)}</span>
                )}
              </div>
              {book.review && <p className="text-sm">{book.review}</p>}
              <div className="flex gap-2 pt-2">
                <Button onClick={startEditing}>編集</Button>
                <Button variant="destructive" onClick={handleDelete}>
                  削除
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-lg">読み聞かせ記録</CardTitle>
          <Button size="sm" onClick={() => navigate(`/records/new?book_id=${numericBookId}`)}>
            記録する
          </Button>
        </CardHeader>
        <CardContent>
          {recordsData && recordsData.data.length > 0 ? (
            <div className="space-y-3">
              {recordsData.data.map((record) => (
                <Link
                  key={record.id}
                  to={`/records/${record.id}`}
                  className="block rounded border p-3 hover:bg-muted/50"
                >
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">{record.read_date}</span>
                    <span className="text-xs text-muted-foreground">
                      {record.children.map((c) => c.name).join(', ')}
                    </span>
                  </div>
                  {record.memo && (
                    <p className="mt-1 truncate text-sm text-muted-foreground">{record.memo}</p>
                  )}
                  {record.tags.length > 0 && (
                    <div className="mt-1 flex gap-1">
                      {record.tags.map((tag) => (
                        <span
                          key={tag.id}
                          className="rounded bg-muted px-1.5 py-0.5 text-xs"
                        >
                          {tag.name}
                        </span>
                      ))}
                    </div>
                  )}
                </Link>
              ))}
              {recordsData.meta.total > 5 && (
                <Link
                  to={`/records?picture_book_id=${numericBookId}`}
                  className="block text-center text-sm text-muted-foreground hover:underline"
                >
                  すべての記録を見る ({recordsData.meta.total}件)
                </Link>
              )}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">まだ記録がありません</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
