import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as booksApi from '../api/books';

export const useSearchGoogleBooks = (query: string) => {
  return useQuery({
    queryKey: ['googleBooks', query],
    queryFn: () => searchGoogleBooksQuery(query),
    enabled: query.length >= 2,
  });
};

async function searchGoogleBooksQuery(query: string) {
  const res = await booksApi.searchGoogleBooks(query);
  return res.data;
}

export const useBooks = (familyId: number, params?: { status?: string; page?: number }) => {
  return useQuery({
    queryKey: ['books', familyId, params],
    queryFn: () => booksApi.getBooks(familyId, params).then((res) => res.data),
    enabled: familyId > 0,
  });
};

export const useBook = (familyId: number, bookId: number) => {
  return useQuery({
    queryKey: ['book', familyId, bookId],
    queryFn: () => booksApi.getBook(familyId, bookId).then((res) => res.data.data),
    enabled: familyId > 0 && bookId > 0,
  });
};

export const useAddBook = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: {
      google_books_id?: string;
      isbn?: string;
      title: string;
      authors: string[];
      thumbnail_url?: string;
    }) => booksApi.addBook(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books', familyId] });
    },
  });
};

export const useUpdateBook = (familyId: number, bookId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { rating?: number | null; read_status: string; review?: string | null }) =>
      booksApi.updateBook(familyId, bookId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['book', familyId, bookId] });
      queryClient.invalidateQueries({ queryKey: ['books', familyId] });
    },
  });
};

export const useRemoveBook = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (bookId: number) => booksApi.removeBook(familyId, bookId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books', familyId] });
    },
  });
};
