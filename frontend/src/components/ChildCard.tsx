import { useState } from 'react';
import type { Child } from '../api/family';
import ChildForm from './ChildForm';
import { Button } from '@/components/ui/button';

interface ChildCardProps {
  child: Child;
  onUpdate: (data: { childId: number; name: string; birthday?: string }) => void;
  onRemove: (childId: number) => void;
}

export default function ChildCard({ child, onUpdate, onRemove }: ChildCardProps) {
  const [isEditing, setIsEditing] = useState(false);

  const handleUpdate = (data: { name: string; birthday?: string }) => {
    onUpdate({ childId: child.id, ...data });
    setIsEditing(false);
  };

  const handleRemove = () => {
    if (window.confirm(`${child.name} を削除しますか？`)) {
      onRemove(child.id);
    }
  };

  if (isEditing) {
    return (
      <div className="rounded-lg border p-3">
        <ChildForm
          defaultValues={{ name: child.name, birthday: child.birthday ?? undefined }}
          onSubmit={handleUpdate}
          onCancel={() => setIsEditing(false)}
          submitLabel="更新"
        />
      </div>
    );
  }

  return (
    <div className="flex items-center justify-between rounded-lg border p-3">
      <div className="flex items-baseline gap-2">
        <span className="font-medium">{child.name}</span>
        {child.age !== null && (
          <span className="text-sm text-muted-foreground">({child.age}歳)</span>
        )}
        {child.birthday && (
          <span className="text-xs text-muted-foreground">{child.birthday}</span>
        )}
      </div>
      <div className="flex gap-2">
        <Button variant="ghost" size="sm" onClick={() => setIsEditing(true)}>
          編集
        </Button>
        <Button variant="ghost" size="sm" className="text-destructive" onClick={handleRemove}>
          削除
        </Button>
      </div>
    </div>
  );
}
