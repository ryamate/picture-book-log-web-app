import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuth } from './useAuth';
import * as familyApi from '../api/family';

export const useFamily = (familyId: number) => {
  return useQuery({
    queryKey: ['family', familyId],
    queryFn: () => familyApi.getFamily(familyId).then((res) => res.data.data),
    enabled: familyId > 0,
  });
};

export const useCreateFamily = () => {
  const { refreshUser } = useAuth();
  return useMutation({
    mutationFn: familyApi.createFamily,
    onSuccess: async () => {
      await refreshUser();
    },
  });
};

export const useUpdateFamily = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { name: string }) => familyApi.updateFamily(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['family', familyId] });
    },
  });
};

export const useMembers = (familyId: number) => {
  return useQuery({
    queryKey: ['members', familyId],
    queryFn: () => familyApi.getMembers(familyId).then((res) => res.data.data),
    enabled: familyId > 0,
  });
};
