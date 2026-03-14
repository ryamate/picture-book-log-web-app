import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as recordsApi from '../api/records';
import type { CreateRecordData, UpdateRecordData } from '../api/records';

export const useRecords = (
  familyId: number,
  params?: {
    child_id?: number;
    picture_book_id?: number;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
  },
) => {
  return useQuery({
    queryKey: ['records', familyId, params],
    queryFn: () => recordsApi.getRecords(familyId, params).then((res) => res.data),
    enabled: familyId > 0,
  });
};

export const useRecord = (familyId: number, recordId: number) => {
  return useQuery({
    queryKey: ['record', familyId, recordId],
    queryFn: () => recordsApi.getRecord(familyId, recordId).then((res) => res.data.data),
    enabled: familyId > 0 && recordId > 0,
  });
};

export const useCreateRecord = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateRecordData) => recordsApi.createRecord(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['records', familyId] });
    },
  });
};

export const useUpdateRecord = (familyId: number, recordId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdateRecordData) => recordsApi.updateRecord(familyId, recordId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['record', familyId, recordId] });
      queryClient.invalidateQueries({ queryKey: ['records', familyId] });
    },
  });
};

export const useDeleteRecord = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (recordId: number) => recordsApi.deleteRecord(familyId, recordId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['records', familyId] });
    },
  });
};

export const useSearchTags = (query: string) => {
  return useQuery({
    queryKey: ['tags', query],
    queryFn: () => recordsApi.searchTags(query).then((res) => res.data.data),
    enabled: query.length >= 1,
  });
};
