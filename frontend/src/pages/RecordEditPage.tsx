import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useRecord, useUpdateRecord } from '../hooks/useRecords';
import { Button } from '@/components/ui/button';
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
    return <p className="text-muted-foreground">読み込み中...</p>;
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
          const { picture_book_id: _, ...updateData } = data;
          await updateRecord.mutateAsync(updateData);
          navigate(`/records/${numericRecordId}`);
        }}
        isSubmitting={updateRecord.isPending}
      />
    </div>
  );
}
