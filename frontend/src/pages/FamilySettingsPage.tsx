import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useAuth } from '../hooks/useAuth';
import { useFamily, useUpdateFamily, useMembers } from '../hooks/useFamily';
import { useChildren, useAddChild, useUpdateChild, useRemoveChild } from '../hooks/useChildren';
import ChildCard from '../components/ChildCard';
import ChildForm from '../components/ChildForm';

export default function FamilySettingsPage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const { data: family } = useFamily(familyId);
  const { data: members } = useMembers(familyId);
  const { data: children } = useChildren(familyId);
  const updateFamily = useUpdateFamily(familyId);
  const addChild = useAddChild(familyId);
  const updateChild = useUpdateChild(familyId);
  const removeChild = useRemoveChild(familyId);
  const [showAddChild, setShowAddChild] = useState(false);

  if (!family) return <div>読み込み中...</div>;

  return (
    <div style={{ maxWidth: 600 }}>
      <h1>家族設定</h1>

      <section style={{ marginBottom: 32 }}>
        <h2>家族名</h2>
        <FamilyNameForm
          currentName={family.name}
          onSubmit={(name) => updateFamily.mutate({ name })}
          isPending={updateFamily.isPending}
        />
      </section>

      <section style={{ marginBottom: 32 }}>
        <h2>メンバー</h2>
        {members?.map((member) => (
          <div key={member.id} style={{ padding: 8, borderBottom: '1px solid #eee' }}>
            {member.name} ({member.email})
          </div>
        ))}
      </section>

      <section>
        <h2>子ども</h2>
        {children?.map((child) => (
          <ChildCard
            key={child.id}
            child={child}
            onUpdate={(data) => updateChild.mutate(data)}
            onRemove={(childId) => removeChild.mutate(childId)}
          />
        ))}
        {showAddChild ? (
          <ChildForm
            onSubmit={(data) => {
              addChild.mutate(data, { onSuccess: () => setShowAddChild(false) });
            }}
            onCancel={() => setShowAddChild(false)}
            submitLabel="追加"
          />
        ) : (
          <button onClick={() => setShowAddChild(true)} style={{ marginTop: 8, padding: '8px 16px' }}>
            子どもを追加
          </button>
        )}
      </section>
    </div>
  );
}

function FamilyNameForm({ currentName, onSubmit, isPending }: { currentName: string; onSubmit: (name: string) => void; isPending: boolean }) {
  const { register, handleSubmit, formState: { errors } } = useForm<{ name: string }>({
    defaultValues: { name: currentName },
  });

  return (
    <form onSubmit={handleSubmit((data) => onSubmit(data.name))} style={{ display: 'flex', gap: 8 }}>
      <input
        {...register('name', { required: '家族名は必須です' })}
        style={{ padding: '6px 8px', flex: 1 }}
      />
      <button type="submit" disabled={isPending} style={{ padding: '6px 16px' }}>
        {isPending ? '更新中...' : '更新'}
      </button>
      {errors.name && <span style={{ color: 'red', fontSize: 12 }}>{errors.name.message}</span>}
    </form>
  );
}
