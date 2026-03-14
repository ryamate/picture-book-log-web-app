import { useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuth } from '../hooks/useAuth';
import { useChildren } from '../hooks/useChildren';
import { useBooks } from '../hooks/useBooks';
import { useSearchTags } from '../hooks/useRecords';
import { useDebounce } from '../hooks/useDebounce';

interface RecordFormProps {
  initialData?: {
    picture_book_id?: number;
    read_date?: string;
    memo?: string;
    children?: { child_id: number; reaction?: string }[];
    tags?: string[];
  };
  onSubmit: (data: {
    picture_book_id: number;
    read_date: string;
    memo?: string;
    children: { child_id: number; reaction?: string }[];
    tags?: string[];
  }) => Promise<void>;
  submitLabel: string;
  isSubmitting: boolean;
  showBookSelector?: boolean;
}

export default function RecordForm({
  initialData,
  onSubmit,
  submitLabel,
  isSubmitting,
  showBookSelector = true,
}: RecordFormProps) {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;

  const { data: booksData } = useBooks(familyId);
  const { data: childrenData } = useChildren(familyId);

  const books = booksData?.data ?? [];
  const children = childrenData ?? [];

  // Form state
  const [bookId, setBookId] = useState<number>(initialData?.picture_book_id ?? 0);
  const [readDate, setReadDate] = useState(
    initialData?.read_date ?? new Date().toISOString().split('T')[0],
  );
  const [selectedChildren, setSelectedChildren] = useState<
    { child_id: number; reaction?: string }[]
  >(initialData?.children ?? []);
  const [tags, setTags] = useState<string[]>(initialData?.tags ?? []);
  const [memo, setMemo] = useState(initialData?.memo ?? '');

  // Error state
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Tag input state
  const [tagInput, setTagInput] = useState('');
  const [showTagSuggestions, setShowTagSuggestions] = useState(false);
  const debouncedTagInput = useDebounce(tagInput, 300);
  const { data: tagSuggestions } = useSearchTags(debouncedTagInput);
  const tagInputRef = useRef<HTMLInputElement>(null);

  const handleChildToggle = (childId: number, checked: boolean) => {
    if (checked) {
      setSelectedChildren((prev) => [...prev, { child_id: childId }]);
    } else {
      setSelectedChildren((prev) => prev.filter((c) => c.child_id !== childId));
    }
  };

  const handleReactionChange = (childId: number, reaction: string) => {
    setSelectedChildren((prev) =>
      prev.map((c) =>
        c.child_id === childId ? { ...c, reaction: reaction || undefined } : c,
      ),
    );
  };

  const isChildSelected = (childId: number) =>
    selectedChildren.some((c) => c.child_id === childId);

  const getChildReaction = (childId: number) =>
    selectedChildren.find((c) => c.child_id === childId)?.reaction ?? '';

  const addTag = (tag: string) => {
    const trimmed = tag.trim();
    if (trimmed && !tags.includes(trimmed)) {
      setTags((prev) => [...prev, trimmed]);
    }
    setTagInput('');
    setShowTagSuggestions(false);
    tagInputRef.current?.focus();
  };

  const removeTag = (tag: string) => {
    setTags((prev) => prev.filter((t) => t !== tag));
  };

  const handleTagKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (tagInput.trim()) {
        addTag(tagInput);
      }
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    // クライアント側バリデーション
    const newErrors: Record<string, string> = {};
    if (showBookSelector && bookId === 0) {
      newErrors.picture_book_id = '絵本を選択してください';
    }
    if (!readDate) {
      newErrors.read_date = '日付を入力してください';
    }
    if (selectedChildren.length === 0) {
      newErrors.children = '子どもを1人以上選択してください';
    }
    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    try {
      await onSubmit({
        picture_book_id: bookId,
        read_date: readDate,
        memo: memo || undefined,
        children: selectedChildren,
        tags: tags.length > 0 ? tags : undefined,
      });
    } catch (err: unknown) {
      // APIバリデーションエラーの表示
      const axiosError = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } };
      if (axiosError.response?.data?.errors) {
        const apiErrors: Record<string, string> = {};
        for (const [key, messages] of Object.entries(axiosError.response.data.errors)) {
          apiErrors[key] = messages[0];
        }
        setErrors(apiErrors);
      } else if (axiosError.response?.data?.message) {
        setErrors({ _general: axiosError.response.data.message });
      }
    }
  };

  // Filter out already-added tags from suggestions
  const filteredSuggestions = (tagSuggestions ?? []).filter(
    (s) => !tags.includes(s.name),
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>読み聞かせ記録</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {errors._general && (
            <p className="rounded bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {errors._general}
            </p>
          )}

          {/* 絵本選択 */}
          {showBookSelector && (
            <div className="space-y-1">
              <Label>絵本</Label>
              <select
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                value={bookId}
                onChange={(e) => setBookId(Number(e.target.value))}
                required
              >
                <option value={0} disabled>
                  絵本を選択してください
                </option>
                {books.map((book: { id: number; title: string }) => (
                  <option key={book.id} value={book.id}>
                    {book.title}
                  </option>
                ))}
              </select>
              {errors.picture_book_id && (
                <p className="text-sm text-destructive">{errors.picture_book_id}</p>
              )}
            </div>
          )}

          {/* 読んだ日 */}
          <div className="space-y-1">
            <Label>読んだ日</Label>
            <Input
              type="date"
              value={readDate}
              onChange={(e) => setReadDate(e.target.value)}
              required
            />
          </div>

          {/* 子ども */}
          <div className="space-y-1">
            <Label>子ども <span className="text-xs text-muted-foreground">（1人以上選択）</span></Label>
            <div className="space-y-2">
              {children.map((child: { id: number; name: string }) => (
                <div key={child.id} className="flex items-center gap-3">
                  <label className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={isChildSelected(child.id)}
                      onChange={(e) => handleChildToggle(child.id, e.target.checked)}
                      className="h-4 w-4 rounded border-gray-300"
                    />
                    <span className="text-sm">{child.name}</span>
                  </label>
                  {isChildSelected(child.id) && (
                    <Input
                      placeholder="リアクション"
                      value={getChildReaction(child.id)}
                      onChange={(e) => handleReactionChange(child.id, e.target.value)}
                      className="flex-1"
                    />
                  )}
                </div>
              ))}
            </div>
            {errors.children && (
              <p className="text-sm text-destructive">{errors.children}</p>
            )}
          </div>

          {/* タグ */}
          <div className="space-y-1">
            <Label>タグ</Label>
            <div className="flex flex-wrap gap-1 mb-2">
              {tags.map((tag) => (
                <span
                  key={tag}
                  className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                >
                  {tag}
                  <button
                    type="button"
                    onClick={() => removeTag(tag)}
                    className="hover:text-destructive"
                  >
                    &times;
                  </button>
                </span>
              ))}
            </div>
            <div className="relative">
              <Input
                ref={tagInputRef}
                placeholder="タグを入力"
                value={tagInput}
                onChange={(e) => {
                  setTagInput(e.target.value);
                  setShowTagSuggestions(true);
                }}
                onKeyDown={handleTagKeyDown}
                onFocus={() => setShowTagSuggestions(true)}
                onBlur={() => {
                  // 少し遅延させてクリックイベントを処理できるようにする
                  setTimeout(() => setShowTagSuggestions(false), 200);
                }}
              />
              {showTagSuggestions && filteredSuggestions.length > 0 && (
                <ul className="absolute z-10 mt-1 w-full rounded-md border bg-background shadow-lg">
                  {filteredSuggestions.map((suggestion) => (
                    <li key={suggestion.id}>
                      <button
                        type="button"
                        className="w-full px-3 py-2 text-left text-sm hover:bg-accent"
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() => addTag(suggestion.name)}
                      >
                        {suggestion.name}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>

          {/* メモ */}
          <div className="space-y-1">
            <Label>メモ</Label>
            <textarea
              className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              value={memo}
              onChange={(e) => setMemo(e.target.value)}
              placeholder="メモを入力"
            />
          </div>

          {/* 送信ボタン */}
          <Button type="submit" disabled={isSubmitting}>
            {submitLabel}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
