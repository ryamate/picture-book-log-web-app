import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useAuth } from '../hooks/useAuth';
import { useFamily, useUpdateFamily, useMembers } from '../hooks/useFamily';
import { useChildren, useAddChild, useUpdateChild, useRemoveChild } from '../hooks/useChildren';
import ChildCard from '../components/ChildCard';
import ChildForm from '../components/ChildForm';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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

  if (!family) return <div className="text-muted-foreground">読み込み中...</div>;

  return (
    <div className="max-w-2xl space-y-6">
      <h1 className="text-2xl font-bold">家族設定</h1>

      <Card>
        <CardHeader>
          <CardTitle>家族名</CardTitle>
        </CardHeader>
        <CardContent>
          <FamilyNameForm
            key={family.name}
            currentName={family.name}
            onSubmit={(name) => updateFamily.mutate({ name })}
            isPending={updateFamily.isPending}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>メンバー</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="divide-y">
            {members?.map((member) => (
              <div key={member.id} className="py-2 text-sm">
                {member.name} ({member.email})
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>子ども</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
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
            <Button variant="outline" onClick={() => setShowAddChild(true)}>
              子どもを追加
            </Button>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function FamilyNameForm({ currentName, onSubmit, isPending }: { currentName: string; onSubmit: (name: string) => void; isPending: boolean }) {
  const { register, handleSubmit, formState: { errors } } = useForm<{ name: string }>({
    defaultValues: { name: currentName },
  });

  return (
    <form onSubmit={handleSubmit((data) => onSubmit(data.name))} className="flex items-center gap-2">
      <Input {...register('name', { required: '家族名は必須です' })} className="flex-1" />
      <Button type="submit" disabled={isPending}>
        {isPending ? '更新中...' : '更新'}
      </Button>
      {errors.name && <span className="text-xs text-destructive">{errors.name.message}</span>}
    </form>
  );
}
