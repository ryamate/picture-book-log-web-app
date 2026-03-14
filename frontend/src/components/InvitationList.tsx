import { useInvitations, useCancelInvitation } from '../hooks/useInvitations';
import type { Invitation } from '../types/invitation';
import { Button } from '@/components/ui/button';

interface Props {
  familyId: number;
}

const statusBadge = (status: Invitation['status']) => {
  switch (status) {
    case 'pending':
      return (
        <span className="rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">
          招待中
        </span>
      );
    case 'accepted':
      return (
        <span className="rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">
          受理済み
        </span>
      );
    case 'expired':
      return (
        <span className="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
          期限切れ
        </span>
      );
  }
};

export default function InvitationList({ familyId }: Props) {
  const { data: invitations, isLoading } = useInvitations(familyId);
  const cancelInvitation = useCancelInvitation(familyId);

  if (isLoading) return <p className="text-sm text-muted-foreground">読み込み中...</p>;
  if (!invitations?.length) return <p className="text-sm text-muted-foreground">招待はありません。</p>;

  const handleCancel = (id: number) => {
    if (window.confirm('この招待をキャンセルしますか？')) {
      cancelInvitation.mutate(id);
    }
  };

  return (
    <div className="divide-y">
      {invitations.map((invitation) => (
        <div key={invitation.id} className="flex items-center justify-between py-2">
          <div className="space-y-0.5">
            <div className="flex items-center gap-2 text-sm">
              <span>{invitation.email}</span>
              {statusBadge(invitation.status)}
            </div>
            <div className="text-xs text-muted-foreground">
              招待日: {new Date(invitation.created_at).toLocaleDateString('ja-JP')}
              {invitation.status === 'pending' && (
                <> / 期限: {new Date(invitation.expires_at).toLocaleDateString('ja-JP')}</>
              )}
            </div>
          </div>
          {invitation.status === 'pending' && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => handleCancel(invitation.id)}
              disabled={cancelInvitation.isPending}
            >
              キャンセル
            </Button>
          )}
        </div>
      ))}
    </div>
  );
}
