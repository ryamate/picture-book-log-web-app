import { useNavigate, useSearchParams } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useCreateRecord } from '../hooks/useRecords';
import { Button } from '@/components/ui/button';
import RecordForm from '../components/RecordForm';

export default function RecordCreatePage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const bookIdFromQuery = searchParams.get('book_id')
    ? Number(searchParams.get('book_id'))
    : undefined;

  const createRecord = useCreateRecord(familyId);

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => navigate(-1)}>
        戻る
      </Button>

      <h1 className="text-2xl font-bold">読み聞かせを記録する</h1>

      <RecordForm
        showBookSelector={true}
        submitLabel="記録する"
        initialData={{ picture_book_id: bookIdFromQuery }}
        onSubmit={async (data) => {
          await createRecord.mutateAsync(data).then(() => {
            navigate('/records');
          });
        }}
        isSubmitting={createRecord.isPending}
      />
    </div>
  );
}
