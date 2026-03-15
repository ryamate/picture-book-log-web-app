import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useRecord, useUpdateRecord } from '../hooks/useRecords';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import RecordForm from '../components/RecordForm';

export default function RecordEditPage() {
  const { recordId } = useParams<{ recordId: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const numericRecordId = Number(recordId);

  const { data: record, isLoading } = useRecord(familyId, numericRecordId);
  const updateRecord = useUpdateRecord(familyId, numericRecordId);

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-24" />
        <Skeleton className="h-8 w-40" />
        <div className="space-y-4">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-24 w-full" />
        </div>
      </div>
    );
  }

  if (!record) {
    return <p className="text-muted-foreground">記録が見つかりませんでした</p>;
  }

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => navigate(`/records/${numericRecordId}`)}>
        キャンセル
      </Button>

      <h1 className="text-2xl font-bold">記録を編集</h1>

      <RecordForm
        showBookSelector={false}
        submitLabel="更新する"
        initialData={{
          picture_book_id: record.picture_book.id,
          read_date: record.read_date,
          memo: record.memo ?? undefined,
          children: record.children.map((c) => ({
            child_id: c.id,
            reaction: c.reaction ?? undefined,
          })),
          tags: record.tags.map((t) => t.name),
        }}
        onSubmit={async (data) => {
          // eslint-disable-next-line @typescript-eslint/no-unused-vars
          const { picture_book_id, ...updateData } = data;
          await updateRecord.mutateAsync(updateData);
          navigate(`/records/${numericRecordId}`);
        }}
        isSubmitting={updateRecord.isPending}
      />
    </div>
  );
}
