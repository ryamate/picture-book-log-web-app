import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useBook, useUpdateBook, useRemoveBook } from '../hooks/useBooks';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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
    return <p className="text-muted-foreground">読み込み中...</p>;
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
          <div className="flex gap-6">
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
    </div>
  );
}
