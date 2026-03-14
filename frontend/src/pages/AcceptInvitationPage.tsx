import { useParams, useNavigate, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useInvitationByToken, useAcceptInvitation } from '../hooks/useInvitations';
import { AxiosError } from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function AcceptInvitationPage() {
  const { token } = useParams<{ token: string }>();
  const navigate = useNavigate();
  const location = useLocation();
  const { user, isLoading: authLoading, refreshUser } = useAuth();

  const {
    data: invitationInfo,
    isLoading: infoLoading,
    isError: infoError,
  } = useInvitationByToken(token);

  const {
    mutate: accept,
    isPending,
    isSuccess,
    isError,
    error,
  } = useAcceptInvitation();

  if (authLoading || infoLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p className="text-muted-foreground">読み込み中...</p>
      </div>
    );
  }

  if (infoError || !invitationInfo) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Card className="w-full max-w-sm">
          <CardContent className="pt-6">
            <p className="text-destructive">この招待は見つかりませんでした。</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!user) {
    return (
      <Navigate
        to="/login"
        state={{ from: location, invitationEmail: invitationInfo.email }}
        replace
      />
    );
  }

  if (invitationInfo.is_expired) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Card className="w-full max-w-sm">
          <CardContent className="pt-6">
            <p className="text-destructive">
              この招待は期限切れです。家族のメンバーに再度招待を依頼してください。
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (invitationInfo.is_accepted) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Card className="w-full max-w-sm">
          <CardContent className="pt-6">
            <p className="text-muted-foreground">この招待は既に受理されています。</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  const handleAccept = () => {
    if (!token) return;
    accept(token, {
      onSuccess: async () => {
        await refreshUser();
        navigate('/');
      },
    });
  };

  const getErrorMessage = () => {
    if (error instanceof AxiosError) {
      const status = error.response?.status;
      if (status === 410)
        return 'この招待は期限切れです。家族のメンバーに再度招待を依頼してください。';
      if (status === 409)
        return (
          error.response?.data?.message || 'この招待は既に受理されています。'
        );
    }
    return 'エラーが発生しました。';
  };

  return (
    <div className="flex min-h-screen items-center justify-center">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>招待の受理</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {isSuccess ? (
            <p className="text-green-600">
              家族に参加しました！リダイレクトしています...
            </p>
          ) : (
            <>
              <p className="text-sm">
                「<span className="font-medium">{invitationInfo.family_name}</span>
                」から招待されています。
              </p>
              {isError && (
                <p className="text-sm text-destructive">{getErrorMessage()}</p>
              )}
              <Button
                onClick={handleAccept}
                disabled={isPending}
                className="w-full"
              >
                {isPending ? '受理しています...' : '招待を受け入れる'}
              </Button>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
