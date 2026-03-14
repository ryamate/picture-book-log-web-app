import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as invitationsApi from '../api/invitations';

export const useInvitations = (familyId: number) => {
  return useQuery({
    queryKey: ['invitations', familyId],
    queryFn: () =>
      invitationsApi.getInvitations(familyId).then((res) => res.data.data),
    enabled: familyId > 0,
  });
};

export const useSendInvitation = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { email: string }) =>
      invitationsApi.sendInvitation(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invitations', familyId] });
    },
  });
};

export const useCancelInvitation = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (invitationId: number) =>
      invitationsApi.cancelInvitation(familyId, invitationId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invitations', familyId] });
    },
  });
};

export const useInvitationByToken = (token: string | undefined) => {
  return useQuery({
    queryKey: ['invitation', token],
    queryFn: () =>
      invitationsApi.getInvitationByToken(token!).then((res) => res.data),
    enabled: !!token,
  });
};

export const useAcceptInvitation = () => {
  return useMutation({
    mutationFn: (token: string) => invitationsApi.acceptInvitation(token),
  });
};
