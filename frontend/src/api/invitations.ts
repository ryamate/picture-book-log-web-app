import apiClient from './client';
import type { Invitation } from '../types/invitation';
import type { Family } from './family';

export interface InvitationInfo {
  email: string;
  family_name: string;
  is_expired: boolean;
  is_accepted: boolean;
}

export const getInvitationByToken = (token: string) =>
  apiClient.get<InvitationInfo>(`/invitations/${token}`);

export const sendInvitation = (familyId: number, data: { email: string }) =>
  apiClient.post<{ message: string; invitation: { data: Invitation } }>(
    `/families/${familyId}/invitations`,
    data,
  );

export const getInvitations = (familyId: number) =>
  apiClient.get<{ data: Invitation[] }>(`/families/${familyId}/invitations`);

export const cancelInvitation = (familyId: number, invitationId: number) =>
  apiClient.delete(`/families/${familyId}/invitations/${invitationId}`);

export const acceptInvitation = (token: string) =>
  apiClient.post<{ message: string; family: { data: Family } }>(
    `/invitations/${token}/accept`,
  );
