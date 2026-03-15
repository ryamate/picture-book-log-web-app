import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useRecord, useDeleteRecord } from '../hooks/useRecords';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

export default function RecordDetailPage() {
  const { recordId } = useParams<{ recordId: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const numericRecordId = Number(recordId);

  const { data: record, isLoading } = useRecord(familyId, numericRecordId);
  const deleteRecord = useDeleteRecord(familyId);

  const handleDelete = async () => {
    if (window.confirm('この記録を削除しますか？')) {
      await deleteRecord.mutateAsync(numericRecordId);
      navigate('/records');
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-24" />
        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-2/5" />
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex flex-col gap-6 md:flex-row">
              <Skeleton className="h-48 w-36 shrink-0 rounded" />
              <div className="flex-1 space-y-2">
                <Skeleton className="h-5 w-3/5" />
                <Skeleton className="h-4 w-2/5" />
                <Skeleton className="h-4 w-1/3" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!record) {
    return <p className="text-muted-foreground">記録が見つかりませんでした</p>;
  }

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => navigate('/records')}>
        一覧に戻る
      </Button>

      <Card>
        <CardHeader>
          <CardTitle>読み聞かせ記録</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col gap-6 md:flex-row">
            {record.picture_book.thumbnail_url ? (
              <img
                src={record.picture_book.thumbnail_url}
                alt={record.picture_book.title}
                className="h-48 w-36 shrink-0 rounded object-cover"
              />
            ) : (
              <div className="flex h-48 w-36 shrink-0 items-center justify-center rounded bg-muted text-sm text-muted-foreground">
                No Image
              </div>
            )}
            <div className="space-y-2">
              <h2 className="text-lg font-medium">{record.picture_book.title}</h2>
              <p className="text-sm text-muted-foreground">読んだ日: {record.read_date}</p>
              {record.recorded_by && (
                <p className="text-sm text-muted-foreground">
                  記録者: {record.recorded_by.name}
                </p>
              )}
            </div>
          </div>

          {record.children.length > 0 && (
            <div>
              <h3 className="mb-1 text-sm font-medium">お子さまの反応</h3>
              <ul className="space-y-1">
                {record.children.map((child) => (
                  <li key={child.id} className="text-sm">
                    <span className="font-medium">{child.name}</span>
                    {child.reaction && (
                      <span className="ml-2 text-muted-foreground">{child.reaction}</span>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {record.tags.length > 0 && (
            <div>
              <h3 className="mb-1 text-sm font-medium">タグ</h3>
              <div className="flex flex-wrap gap-1">
                {record.tags.map((tag) => (
                  <span
                    key={tag.id}
                    className="inline-block rounded bg-muted px-2 py-0.5 text-sm"
                  >
                    {tag.name}
                  </span>
                ))}
              </div>
            </div>
          )}

          {record.memo && (
            <div>
              <h3 className="mb-1 text-sm font-medium">メモ</h3>
              <p className="whitespace-pre-wrap text-sm">{record.memo}</p>
            </div>
          )}

          <div className="flex gap-2 pt-2">
            <Button onClick={() => navigate(`/records/${numericRecordId}/edit`)}>
              編集
            </Button>
            <Button
              variant="destructive"
              onClick={handleDelete}
              disabled={deleteRecord.isPending}
            >
              削除
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
