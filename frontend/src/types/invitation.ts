export interface Invitation {
  id: number;
  email: string;
  status: 'pending' | 'accepted' | 'expired';
  invited_by: { id: number; name: string };
  expires_at: string;
  accepted_at: string | null;
  created_at: string;
}
