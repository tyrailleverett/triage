import { Button } from '@/Components/ui/button';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}

export default function Pagination({ currentPage, lastPage, onPageChange }: PaginationProps): React.JSX.Element | null {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between">
            <p className="text-xs text-muted-foreground">
                Page {currentPage} of {lastPage}
            </p>
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => onPageChange(currentPage - 1)}
                    disabled={currentPage <= 1}
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => onPageChange(currentPage + 1)}
                    disabled={currentPage >= lastPage}
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
