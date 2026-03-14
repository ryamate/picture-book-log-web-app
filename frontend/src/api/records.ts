import apiClient from './client';
import type { PaginatedResponse } from './books';

export interface ChildReaction {
  id: number;
  name: string;
  reaction: string | null;
}

export interface ReadRecord {
  id: number;
  picture_book: {
    id: number;
    title: string;
    thumbnail_url: string | null;
  };
  read_date: string;
  memo: string | null;
  children: ChildReaction[];
  tags: { id: number; name: string }[];
  recorded_by: { id: number; name: string } | null;
  created_at: string;
}

export interface CreateRecordData {
  picture_book_id: number;
  read_date: string;
  memo?: string;
  children: { child_id: number; reaction?: string }[];
  tags?: string[];
}

export type UpdateRecordData = Omit<CreateRecordData, 'picture_book_id'>;

export const getRecords = (
  familyId: number,
  params?: {
    child_id?: number;
    picture_book_id?: number;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
  },
) => apiClient.get<PaginatedResponse<ReadRecord>>(`/families/${familyId}/records`, { params });

export const createRecord = (familyId: number, data: CreateRecordData) =>
  apiClient.post<{ data: ReadRecord }>(`/families/${familyId}/records`, data);

export const getRecord = (familyId: number, recordId: number) =>
  apiClient.get<{ data: ReadRecord }>(`/families/${familyId}/records/${recordId}`);

export const updateRecord = (familyId: number, recordId: number, data: UpdateRecordData) =>
  apiClient.put<{ data: ReadRecord }>(`/families/${familyId}/records/${recordId}`, data);

export const deleteRecord = (familyId: number, recordId: number) =>
  apiClient.delete(`/families/${familyId}/records/${recordId}`);

export const searchTags = (query: string) =>
  apiClient.get<{ data: { id: number; name: string }[] }>('/tags', { params: { q: query } });
