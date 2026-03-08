import { useState } from 'react';
import type { Child } from '../api/family';
import ChildForm from './ChildForm';

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
      <div style={{ padding: 12, border: '1px solid #ddd', borderRadius: 8, marginBottom: 8 }}>
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
    <div style={{ padding: 12, border: '1px solid #ddd', borderRadius: 8, marginBottom: 8, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
      <div>
        <strong>{child.name}</strong>
        {child.age !== null && <span style={{ marginLeft: 8, color: '#666' }}>({child.age}歳)</span>}
        {child.birthday && <span style={{ marginLeft: 8, color: '#999', fontSize: 12 }}>{child.birthday}</span>}
      </div>
      <div style={{ display: 'flex', gap: 8 }}>
        <button onClick={() => setIsEditing(true)}>編集</button>
        <button onClick={handleRemove} style={{ color: 'red' }}>削除</button>
      </div>
    </div>
  );
}
