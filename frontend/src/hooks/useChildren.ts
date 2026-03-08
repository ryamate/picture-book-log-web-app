import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as familyApi from '../api/family';

export const useChildren = (familyId: number) => {
  return useQuery({
    queryKey: ['children', familyId],
    queryFn: () => familyApi.getChildren(familyId).then((res) => res.data.data),
    enabled: familyId > 0,
  });
};

export const useAddChild = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { name: string; birthday?: string }) => familyApi.addChild(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['children', familyId] });
    },
  });
};

export const useUpdateChild = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ childId, ...data }: { childId: number; name: string; birthday?: string }) =>
      familyApi.updateChild(familyId, childId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['children', familyId] });
    },
  });
};

export const useRemoveChild = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (childId: number) => familyApi.removeChild(familyId, childId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['children', familyId] });
    },
  });
};
