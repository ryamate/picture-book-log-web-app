import { Button } from '@/components/ui/button';

export default function ErrorFallback({ onRetry }: { onRetry: () => void }) {
  return (
    <div className="flex min-h-[50vh] flex-col items-center justify-center gap-4 p-8 text-center">
      <h2 className="text-xl font-semibold text-foreground">
        予期しないエラーが発生しました
      </h2>
      <p className="text-muted-foreground">
        申し訳ありません。ページの再読み込みをお試しください。
      </p>
      <div className="flex gap-3">
        <Button onClick={onRetry}>再試行</Button>
        <Button variant="outline" onClick={() => (window.location.href = '/')}>
          トップに戻る
        </Button>
      </div>
    </div>
  );
}
