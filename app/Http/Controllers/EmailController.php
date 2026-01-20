<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use App\Models\EmailLog; 

class EmailController extends Controller
{
    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }


    /**
 * Get specific email details
 */
public function getEmailDetails($messageId, Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        $client = $this->googleService->getAuthenticatedClient($user->id);
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with Google.'
            ], 401);
        }

        $gmail = new \Google\Service\Gmail($client);
        
        // Get full message with body
        $message = $gmail->users_messages->get('me', $messageId, [
            'format' => 'full'
        ]);
        
        $payload = $message->getPayload();
        $headers = $payload->getHeaders();
        $body = $this->getMessageBody($payload);
        
        $headerMap = [];
        foreach ($headers as $header) {
            $headerMap[strtolower($header->getName())] = $header->getValue();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->getId(),
                'thread_id' => $message->getThreadId(),
                'subject' => $headerMap['subject'] ?? '',
                'from' => $headerMap['from'] ?? '',
                'to' => $headerMap['to'] ?? '',
                'cc' => $headerMap['cc'] ?? '',
                'bcc' => $headerMap['bcc'] ?? '',
                'date' => $headerMap['date'] ?? '',
                'body' => $body,
                'html_body' => $this->getHtmlBody($payload),
                'plain_body' => $this->getPlainBody($payload),
                'attachments' => $this->getAttachments($gmail, $messageId, $payload),
                'label_ids' => $message->getLabelIds(),
                'snippet' => $message->getSnippet()
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching email details', [
            'user_id' => $user->id,
            'message_id' => $messageId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch email details: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Extract message body
 */
private function getMessageBody($payload)
{
    if ($payload->getBody()->getData()) {
        return $this->base64Decode($payload->getBody()->getData());
    }
    
    $parts = $payload->getParts();
    if ($parts) {
        foreach ($parts as $part) {
            if ($part->getBody()->getData()) {
                return $this->base64Decode($part->getBody()->getData());
            }
        }
    }
    
    return '';
}

/**
 * Get HTML body
 */
private function getHtmlBody($payload)
{
    return $this->getBodyByMimeType($payload, 'text/html');
}

/**
 * Get plain text body
 */
private function getPlainBody($payload)
{
    return $this->getBodyByMimeType($payload, 'text/plain');
}

/**
 * Get body by MIME type
 */
private function getBodyByMimeType($payload, $mimeType)
{
    if ($payload->getMimeType() === $mimeType && $payload->getBody()->getData()) {
        return $this->base64Decode($payload->getBody()->getData());
    }
    
    $parts = $payload->getParts();
    if ($parts) {
        foreach ($parts as $part) {
            if ($part->getMimeType() === $mimeType && $part->getBody()->getData()) {
                return $this->base64Decode($part->getBody()->getData());
            }
            
            // Check nested parts
            $subParts = $part->getParts();
            if ($subParts) {
                foreach ($subParts as $subPart) {
                    if ($subPart->getMimeType() === $mimeType && $subPart->getBody()->getData()) {
                        return $this->base64Decode($subPart->getBody()->getData());
                    }
                }
            }
        }
    }
    
    return '';
}

/**
 * Get attachments list
 */
private function getAttachments($gmail, $messageId, $payload)
{
    $attachments = [];
    $parts = $payload->getParts();
    
    if (!$parts) {
        return $attachments;
    }
    
    foreach ($parts as $part) {
        if ($part->getFilename() && $part->getBody()->getAttachmentId()) {
            $attachments[] = [
                'id' => $part->getBody()->getAttachmentId(),
                'filename' => $part->getFilename(),
                'mime_type' => $part->getMimeType(),
                'size' => $part->getBody()->getSize()
            ];
        }
        
        $subParts = $part->getParts();
        if ($subParts) {
            foreach ($subParts as $subPart) {
                if ($subPart->getFilename() && $subPart->getBody()->getAttachmentId()) {
                    $attachments[] = [
                        'id' => $subPart->getBody()->getAttachmentId(),
                        'filename' => $subPart->getFilename(),
                        'mime_type' => $subPart->getMimeType(),
                        'size' => $subPart->getBody()->getSize()
                    ];
                }
            }
        }
    }
    
    return $attachments;
}
 public function getEmailsByUser(Request $request, $userId = null)
    {
        $authenticatedUser = $request->user();
        
        if (!$authenticatedUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // If user ID is provided, check if authenticated user has permission
        // Otherwise, use authenticated user's ID
        $targetUserId = $userId ?? $authenticatedUser->id;
        
        // If trying to access another user's emails, check permissions
        if ($targetUserId != $authenticatedUser->id) {
            // Check if user is admin or has permission to view other users' emails
            // You can implement your own permission logic here
            $isAdmin = $this->isAdmin($authenticatedUser);
            $hasPermission = $this->hasPermissionToViewUserEmails($authenticatedUser, $targetUserId);
            
            if (!$isAdmin && !$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this user\'s emails'
                ], 403);
            }
        }

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'status' => 'in:sent,failed',
            'search' => 'string|max:255',
            'lead_id' => 'exists:leads,id',
            'sort_by' => 'in:sent_at,subject,status',
            'sort_order' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Build query
            $query = EmailLog::where('user_id', $targetUserId)
                ->with(['lead' => function($query) {
                    $query->select('id', 'full_name', 'email', 'company');
                }])
                ->select([
                    'id',
                    'lead_id',
                    'user_id',
                    'organisation_id',
                    'to_email',
                    'subject',
                    'body',
                    'message_id',
                    'status',
                    'error_message',
                    'sent_at',
                    'scheduled_for',
                    'created_at'
                ]);

            // Apply date filters
            if ($request->has('start_date')) {
                $query->whereDate('sent_at', '>=', $request->start_date);
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('sent_at', '<=', $request->end_date);
            }

            // Apply status filter
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Apply lead filter
            if ($request->has('lead_id')) {
                $query->where('lead_id', $request->lead_id);
            }

            // Apply search filter
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('subject', 'like', "%{$searchTerm}%")
                      ->orWhere('to_email', 'like', "%{$searchTerm}%")
                      ->orWhere('body', 'like', "%{$searchTerm}%");
                });
            }

            // Apply sorting
            $sortBy = $request->sort_by ?? 'sent_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Get paginated results
            $perPage = $request->per_page ?? 20;
            $page = $request->page ?? 1;
            
            $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);

            // Get statistics
            $totalSent = EmailLog::where('user_id', $targetUserId)
                ->where('status', 'sent')
                ->count();

            $totalFailed = EmailLog::where('user_id', $targetUserId)
                ->where('status', 'failed')
                ->count();

            $lastSent = EmailLog::where('user_id', $targetUserId)
                ->where('status', 'sent')
                ->latest('sent_at')
                ->value('sent_at');

            // Format response
            $formattedEmails = $paginatedResults->map(function ($email) {
                return [
                    'id' => $email->id,
                    'lead' => $email->lead ? [
                        'id' => $email->lead->id,
                        'name' => $email->lead->full_name,
                        'email' => $email->lead->email,
                        'company' => $email->lead->company
                    ] : null,
                    'to_email' => $email->to_email,
                    'subject' => $email->subject,
                    'body' => $email->body, // You might want to truncate this for list view
                    'body_preview' => $email->body ? substr(strip_tags($email->body), 0, 100) . '...' : null,
                    'message_id' => $email->message_id,
                    'status' => $email->status,
                    'error_message' => $email->error_message,
                    'sent_at' => $email->sent_at ? $email->sent_at->toISOString() : null,
                    'scheduled_for' => $email->scheduled_for ? $email->scheduled_for->toISOString() : null,
                    'created_at' => $email->created_at->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'emails' => $formattedEmails,
                    'statistics' => [
                        'total_sent' => $totalSent,
                        'total_failed' => $totalFailed,
                        'last_sent' => $lastSent ? $lastSent->toISOString() : null,
                        'success_rate' => $totalSent + $totalFailed > 0 
                            ? round(($totalSent / ($totalSent + $totalFailed)) * 100, 2) 
                            : 0
                    ],
                    'pagination' => [
                        'current_page' => $paginatedResults->currentPage(),
                        'per_page' => $paginatedResults->perPage(),
                        'total' => $paginatedResults->total(),
                        'total_pages' => $paginatedResults->lastPage(),
                        'has_more' => $paginatedResults->hasMorePages()
                    ],
                    'filters' => [
                        'user_id' => $targetUserId,
                        'start_date' => $request->start_date ?? null,
                        'end_date' => $request->end_date ?? null,
                        'status' => $request->status ?? null,
                        'search' => $request->search ?? null,
                        'lead_id' => $request->lead_id ?? null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching user emails', [
                'user_id' => $authenticatedUser->id,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email details by ID from email_logs
     */
    public function getEmailLogDetails($emailLogId, Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $emailLog = EmailLog::with(['lead' => function($query) {
                    $query->select('id', 'full_name', 'email', 'company', 'position', 'location');
                }])
                ->where('id', $emailLogId)
                ->first();

            if (!$emailLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found'
                ], 404);
            }

            // Check permissions
            if ($emailLog->user_id != $user->id) {
                $isAdmin = $this->isAdmin($user);
                $hasPermission = $this->hasPermissionToViewUserEmails($user, $emailLog->user_id);
                
                if (!$isAdmin && !$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to view this email'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $emailLog->id,
                    'lead' => $emailLog->lead ? [
                        'id' => $emailLog->lead->id,
                        'name' => $emailLog->lead->full_name,
                        'email' => $emailLog->lead->email,
                        'company' => $emailLog->lead->company,
                        'position' => $emailLog->lead->position,
                        'location' => $emailLog->lead->location
                    ] : null,
                    'user_id' => $emailLog->user_id,
                    'organisation_id' => $emailLog->organisation_id,
                    'to_email' => $emailLog->to_email,
                    'subject' => $emailLog->subject,
                    'body' => $emailLog->body,
                    'message_id' => $emailLog->message_id,
                    'status' => $emailLog->status,
                    'error_message' => $emailLog->error_message,
                    'sent_at' => $emailLog->sent_at ? $emailLog->sent_at->toISOString() : null,
                    'scheduled_for' => $emailLog->scheduled_for ? $emailLog->scheduled_for->toISOString() : null,
                    'created_at' => $emailLog->created_at->toISOString(),
                    'updated_at' => $emailLog->updated_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching email log details', [
                'user_id' => $user->id,
                'email_log_id' => $emailLogId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email statistics for a user
     */
    public function getUserEmailStatistics(Request $request, $userId = null)
    {
        $authenticatedUser = $request->user();
        
        if (!$authenticatedUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $targetUserId = $userId ?? $authenticatedUser->id;
        
        // Check permissions if accessing another user's statistics
        if ($targetUserId != $authenticatedUser->id) {
            $isAdmin = $this->isAdmin($authenticatedUser);
            $hasPermission = $this->hasPermissionToViewUserEmails($authenticatedUser, $targetUserId);
            
            if (!$isAdmin && !$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this user\'s statistics'
                ], 403);
            }
        }

        try {
            // Get daily statistics for the last 30 days
            $startDate = now()->subDays(30);
            
            $dailyStats = EmailLog::where('user_id', $targetUserId)
                ->where('sent_at', '>=', $startDate)
                ->selectRaw('DATE(sent_at) as date, 
                    COUNT(*) as total_emails,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get lead-specific statistics
            $leadStats = EmailLog::where('user_id', $targetUserId)
                ->whereNotNull('lead_id')
                ->with('lead:id,full_name')
                ->selectRaw('lead_id, 
                    COUNT(*) as email_count,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count')
                ->groupBy('lead_id')
                ->orderByDesc('email_count')
                ->limit(10)
                ->get();

            // Get overall statistics
            $overallStats = [
                'total_emails' => EmailLog::where('user_id', $targetUserId)->count(),
                'sent_emails' => EmailLog::where('user_id', $targetUserId)->where('status', 'sent')->count(),
                'failed_emails' => EmailLog::where('user_id', $targetUserId)->where('status', 'failed')->count(),
                'success_rate' => EmailLog::where('user_id', $targetUserId)->count() > 0 
                    ? round((EmailLog::where('user_id', $targetUserId)->where('status', 'sent')->count() / 
                            EmailLog::where('user_id', $targetUserId)->count()) * 100, 2) 
                    : 0,
                'last_30_days' => EmailLog::where('user_id', $targetUserId)
                    ->where('sent_at', '>=', $startDate)
                    ->count(),
                'average_per_day' => EmailLog::where('user_id', $targetUserId)->count() > 0 
                    ? round(EmailLog::where('user_id', $targetUserId)->count() / 
                            max(1, floor((now()->diffInDays(EmailLog::where('user_id', $targetUserId)
                                ->oldest('sent_at')
                                ->value('sent_at') ?? now())))), 2) 
                    : 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $targetUserId,
                    'overall_statistics' => $overallStats,
                    'daily_statistics' => $dailyStats,
                    'top_leads' => $leadStats->map(function ($stat) {
                        return [
                            'lead_id' => $stat->lead_id,
                            'lead_name' => $stat->lead ? $stat->lead->full_name : 'Unknown',
                            'email_count' => $stat->email_count,
                            'sent_count' => $stat->sent_count,
                            'failed_count' => $stat->failed_count
                        ];
                    }),
                    'time_period' => [
                        'start_date' => $startDate->toISOString(),
                        'end_date' => now()->toISOString()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching user email statistics', [
                'user_id' => $authenticatedUser->id,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to check if user is admin
     * You need to implement this based on your user roles system
     */
    private function isAdmin($user)
    {
        // Implement your admin check logic here
        // Example: return $user->role === 'admin';
        return false; // Default to false
    }

    /**
     * Helper method to check if user has permission to view another user's emails
     * You need to implement this based on your permission system
     */
    private function hasPermissionToViewUserEmails($user, $targetUserId)
    {
        // Implement your permission check logic here
        // Example: Check if users are in the same team/department
        // return $user->department_id === User::find($targetUserId)->department_id;
        return false; // Default to false
    }

/**
 * Decode base64 URL safe string
 */
private function base64Decode($data)
{
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}


    /**
 * Get sent emails for the logged-in user
 */
public function getSentEmails(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'max_results' => 'integer|min:1|max:500',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'search' => 'string|max:255',
            'label' => 'string|in:inbox,sent,drafts,trash'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get authenticated Gmail client
        $client = $this->googleService->getAuthenticatedClient($user->id);
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with Google. Please reconnect your Google account.'
            ], 401);
        }

        $gmail = new \Google\Service\Gmail($client);
        
        // Build query for sent emails
        $query = 'in:sent';
        
        // Add search term if provided
        if ($request->has('search') && $request->search) {
            $query .= ' ' . $request->search;
        }
        
        // Add date filters
        if ($request->has('start_date')) {
            $startTimestamp = strtotime($request->start_date);
            $query .= ' after:' . (int)($startTimestamp / 86400);
        }
        
        if ($request->has('end_date')) {
            $endTimestamp = strtotime($request->end_date);
            $query .= ' before:' . (int)(($endTimestamp / 86400) + 1);
        }

        // Get messages with pagination
        $pageToken = null;
        $maxResults = $request->max_results ?? 50;
        $perPage = $request->per_page ?? 20;
        $page = $request->page ?? 1;
        
        // Calculate offset for Google API (it doesn't support offset natively)
        if ($page > 1) {
            $maxResults = $perPage * $page;
        }

        $messages = [];
        $totalResults = 0;
        $nextPageToken = null;
        
        do {
            $params = [
                'maxResults' => min(100, $maxResults - count($messages)),
                'q' => $query,
                'labelIds' => ['SENT']
            ];
            
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }
            
            $list = $gmail->users_messages->listUsersMessages('me', $params);
            $messageIds = $list->getMessages();
            $totalResults = $list->getResultSizeEstimate();
            $nextPageToken = $list->getNextPageToken();
            
            if ($messageIds) {
                foreach ($messageIds as $messageId) {
                    try {
                        // Get full message details
                        $message = $gmail->users_messages->get('me', $messageId->getId(), [
                            'format' => 'metadata',
                            'metadataHeaders' => ['From', 'To', 'Subject', 'Date', 'Cc', 'Bcc']
                        ]);
                        
                        $messages[] = $this->formatMessage($message);
                        
                        // Stop if we have enough for current page
                        if (count($messages) >= $maxResults) {
                            break 2;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to fetch message details', [
                            'message_id' => $messageId->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            $pageToken = $nextPageToken;
            
        } while ($pageToken && count($messages) < $maxResults);

        // Apply pagination to the results
        $paginatedMessages = array_slice($messages, ($page - 1) * $perPage, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => [
                'emails' => $paginatedMessages,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_results' => $totalResults,
                    'total_pages' => ceil($totalResults / $perPage),
                    'has_more' => count($messages) > ($page * $perPage)
                ],
                'filters' => [
                    'query' => $query,
                    'start_date' => $request->start_date ?? null,
                    'end_date' => $request->end_date ?? null,
                    'search' => $request->search ?? null
                ]
            ]
        ]);

    } catch (\Google\Service\Exception $e) {
        Log::error('Google API error fetching sent emails', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch sent emails: ' . $e->getMessage()
        ], 400);
        
    } catch (\Exception $e) {
        Log::error('Error fetching sent emails', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch sent emails: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Format Gmail message for response
 */
private function formatMessage($message)
{
    $headers = $message->getPayload()->getHeaders();
    $headerMap = [];
    
    foreach ($headers as $header) {
        $headerMap[strtolower($header->getName())] = $header->getValue();
    }
    
    // Extract email addresses from "To" field
    $toEmails = [];
    if (isset($headerMap['to'])) {
        $toEmails = $this->extractEmails($headerMap['to']);
    }
    
    return [
        'id' => $message->getId(),
        'thread_id' => $message->getThreadId(),
        'subject' => $headerMap['subject'] ?? '(No Subject)',
        'from' => $headerMap['from'] ?? '',
        'to' => $headerMap['to'] ?? '',
        'to_emails' => $toEmails,
        'cc' => $headerMap['cc'] ?? '',
        'bcc' => $headerMap['bcc'] ?? '',
        'date' => $headerMap['date'] ?? '',
        'snippet' => $message->getSnippet(),
        'internal_date' => $message->getInternalDate(),
        'label_ids' => $message->getLabelIds(),
        'size_estimate' => $message->getSizeEstimate(),
        'has_attachments' => $this->hasAttachments($message->getPayload())
    ];
}

/**
 * Extract email addresses from header string
 */
private function extractEmails($headerString)
{
    $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    preg_match_all($pattern, $headerString, $matches);
    return $matches[0] ?? [];
}

/**
 * Check if message has attachments
 */
private function hasAttachments($payload)
{
    $parts = $payload->getParts();
    
    if (!$parts) {
        return false;
    }
    
    foreach ($parts as $part) {
        if ($part->getFilename() && strlen($part->getFilename()) > 0) {
            return true;
        }
        
        $subParts = $part->getParts();
        if ($subParts) {
            foreach ($subParts as $subPart) {
                if ($subPart->getFilename() && strlen($subPart->getFilename()) > 0) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

    /**
     * Send an email to a lead
     */
    public function sendToLead(Request $request, Lead $lead)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user has access to this lead
        if ($lead->organisation_id !== $user->organisation_id) {
            return response()->json(['message' => 'Unauthorized for this lead'], 403);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if lead has email
            if (!$lead->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead does not have an email address'
                ], 400);
            }

            // Send email via Google Service
            $result = $this->sendEmail($user->id, [
                'to' => $lead->email,
                'subject' => $request->subject,
                'body' => $request->body,
                'lead_id' => $lead->id,
            ]);

            if ($result['success']) {
                // Log the email sent in lead history with body
                $this->logEmailSent($lead, $request->subject, $result, $request->body);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'data' => [
                        'message_id' => $result['message_id'] ?? null,
                        'lead' => [
                            'id' => $lead->id,
                            'name' => $lead->full_name,
                            'email' => $lead->email
                        ]
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error sending email to lead', [
                'user_id' => $user->id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk emails to multiple leads
     */
    public function sendBulkEmails(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'personalize' => 'boolean',
            'batch_size' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get leads that belong to user's organization
            $leads = Lead::whereIn('id', $request->lead_ids)
                ->where('organisation_id', $user->organisation_id)
                ->whereNotNull('email')
                ->get();

            if ($leads->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid leads found with email addresses'
                ], 400);
            }

            $results = [
                'success' => [],
                'failed' => []
            ];

            $batchSize = $request->batch_size ?? 10;
            $delay = 0;

            foreach ($leads->chunk($batchSize) as $leadBatch) {
                foreach ($leadBatch as $lead) {
                    try {
                        // Personalize email body if requested
                        $body = $this->personalizeEmail($request->body, $lead, $request->personalize ?? false);

                        $result = $this->sendEmail($user->id, [
                            'to' => $lead->email,
                            'subject' => $this->personalizeSubject($request->subject, $lead, $request->personalize ?? false),
                            'body' => $body,
                            'lead_id' => $lead->id,
                            'delay' => $delay,
                        ]);

                        if ($result['success']) {
                            $results['success'][] = [
                                'lead_id' => $lead->id,
                                'email' => $lead->email,
                                'message_id' => $result['message_id'] ?? null
                            ];
                            
                            // Log successful email with body
                            $this->logEmailSent($lead, $request->subject, $result, $body);
                        } else {
                            $results['failed'][] = [
                                'lead_id' => $lead->id,
                                'email' => $lead->email,
                                'error' => $result['message']
                            ];
                        }

                        $delay += 1;

                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'lead_id' => $lead->id,
                            'email' => $lead->email,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk email operation completed',
                'data' => [
                    'total_processed' => count($leads),
                    'success_count' => count($results['success']),
                    'failed_count' => count($results['failed']),
                    'results' => $results
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending bulk emails', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a custom email (not to a lead)
     */
    public function sendCustomEmail(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'to' => 'required|array|min:1',
            'to.*' => 'email',
            'cc' => 'nullable|array',
            'cc.*' => 'email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_html' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->sendEmail($user->id, [
                'to' => $request->to,
                'cc' => $request->cc,
                'bcc' => $request->bcc,
                'subject' => $request->subject,
                'body' => $request->body,
                'is_html' => $request->is_html ?? true,
                'attachments' => $request->file('attachments', []),
            ]);

            if ($result['success']) {
                // Log custom email (optional)
                if (Schema::hasTable('email_logs')) {
                    $this->logEmailSent(null, $request->subject, $result, $request->body);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'data' => [
                        'message_id' => $result['message_id'] ?? null,
                        'recipients' => [
                            'to' => $request->to,
                            'cc' => $request->cc ?? [],
                            'bcc' => $request->bcc ?? []
                        ]
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error sending custom email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email history for a lead
     */
    public function getLeadEmailHistory(Lead $lead, Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($lead->organisation_id !== $user->organisation_id) {
            return response()->json(['message' => 'Unauthorized for this lead'], 403);
        }

        // Check if email_logs table exists
        if (Schema::hasTable('email_logs')) {
            $logs = \App\Models\EmailLog::where('lead_id', $lead->id)
                ->orderBy('sent_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $lead->id,
                    'emails' => $logs,
                    'total_sent' => $logs->count(),
                    'last_sent' => $logs->first() ? $logs->first()->sent_at : null
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'lead_id' => $lead->id,
                'emails' => [],
                'total_sent' => 0,
                'last_sent' => null
            ]
        ]);
    }

    /**
     * Check if user can send emails (connection status)
     */
    public function checkEmailCapability(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $result = $this->googleService->checkConnection($user->id);

        return response()->json([
            'can_send_emails' => $result['connected'] ?? false,
            'provider_email' => $result['email'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
            'message' => $result['message'] ?? 'Not connected'
        ]);
    }
    
    /**
     * Private method to send email via Google Service
     */
    private function sendEmail($userId, array $data)
    {
        try {
            $client = $this->googleService->getAuthenticatedClient($userId);
            
            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'Failed to authenticate with Google. Please reconnect your Google account.'
                ];
            }

            $gmail = new \Google\Service\Gmail($client);
            $message = new \Google\Service\Gmail\Message();

            $to = is_array($data['to']) ? implode(', ', $data['to']) : $data['to'];
            
            $headers = [
                'From: ' . $this->getSenderEmail($userId),
                'To: ' . $to,
                'Subject: ' . $data['subject'],
                'Content-Type: ' . ($data['is_html'] ?? true ? 'text/html; charset=utf-8' : 'text/plain; charset=utf-8'),
            ];

            if (!empty($data['cc'])) {
                $headers[] = 'Cc: ' . implode(', ', $data['cc']);
            }

            if (!empty($data['bcc'])) {
                $headers[] = 'Bcc: ' . implode(', ', $data['bcc']);
            }

            $boundary = null;
            if (!empty($data['attachments'])) {
                $boundary = uniqid('boundary_');
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
            }

            $rawMessage = implode("\r\n", $headers) . "\r\n\r\n";
            
            if ($boundary) {
                $rawMessage .= "--{$boundary}\r\n";
                $rawMessage .= "Content-Type: " . ($data['is_html'] ?? true ? 'text/html' : 'text/plain') . "; charset=utf-8\r\n\r\n";
                $rawMessage .= $data['body'] . "\r\n\r\n";
                
                foreach ($data['attachments'] as $attachment) {
                    $rawMessage .= "--{$boundary}\r\n";
                    $rawMessage .= "Content-Type: " . $attachment->getMimeType() . "; name=\"" . $attachment->getClientOriginalName() . "\"\r\n";
                    $rawMessage .= "Content-Disposition: attachment; filename=\"" . $attachment->getClientOriginalName() . "\"\r\n";
                    $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $rawMessage .= chunk_split(base64_encode(file_get_contents($attachment->getRealPath()))) . "\r\n";
                }
                
                $rawMessage .= "--{$boundary}--";
            } else {
                $rawMessage .= $data['body'];
            }

            $message->setRaw(base64_encode($rawMessage));

            if (isset($data['delay']) && $data['delay'] > 0) {
                sleep($data['delay']);
            }

            $result = $gmail->users_messages->send('me', $message);

            Log::info('Email sent successfully', [
                'user_id' => $userId,
                'to' => $data['to'],
                'subject' => $data['subject'],
                'message_id' => $result->getId()
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'message_id' => $result->getId(),
                'sent_at' => now()->toDateTimeString()
            ];

        } catch (\Google\Service\Exception $e) {
            Log::error('Google API error sending email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'details' => $e->getErrors() ?? []
            ]);

            return [
                'success' => false,
                'message' => 'Google API error: ' . $e->getMessage()
            ];
            
        } catch (\Exception $e) {
            Log::error('Error sending email', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Personalize email body with lead data
     */
    private function personalizeEmail($body, Lead $lead, $personalize = true)
    {
        if (!$personalize) {
            return $body;
        }

        $replacements = [
            '{{lead_name}}' => $lead->full_name,
            '{{first_name}}' => explode(' ', $lead->full_name)[0] ?? $lead->full_name,
            '{{company}}' => $lead->company ?? '',
            '{{position}}' => $lead->position ?? '',
            '{{location}}' => $lead->location ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $body
        );
    }

    /**
     * Personalize subject line
     */
    private function personalizeSubject($subject, Lead $lead, $personalize = true)
    {
        if (!$personalize) {
            return $subject;
        }

        $replacements = [
            '{{lead_name}}' => $lead->full_name,
            '{{first_name}}' => explode(' ', $lead->full_name)[0] ?? $lead->full_name,
            '{{company}}' => $lead->company ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $subject
        );
    }

    /**
     * Get sender email from connected Google account
     */
    private function getSenderEmail($userId)
    {
        $provider = \App\Models\EmailProvider::where('user_id', $userId)
            ->where('provider', 'google')
            ->first();

        return $provider ? $provider->provider_email : config('mail.from.address');
    }

    /**
     * Log email sent to lead
     */
    private function logEmailSent($lead, $subject, array $result, $body = null)
    {
        $user = auth()->user();
        
        try {
            // Check if table exists
            if (!Schema::hasTable('email_logs')) {
                return;
            }
            
            // For custom emails, lead might be null
            $leadData = [
                'lead_id' => $lead ? $lead->id : null,
                'user_id' => $user->id,
                'organisation_id' => $user->organisation_id,
                'to_email' => $lead ? $lead->email : 'custom_email@example.com',
                'subject' => $subject,
                'body' => $body, // This was missing - now added
                'sent_at' => now(),
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['success'] ? 'sent' : 'failed',
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Unknown error')
            ];

            \App\Models\EmailLog::create($leadData);

            // Update lead's last contacted date if it's a lead email
            if ($lead && $lead->email) {
                $lead->update([
                    'last_contacted_at' => now()
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to log email', [
                'error' => $e->getMessage(),
                'lead_id' => $lead ? $lead->id : null,
                'user_id' => $user->id ?? null
            ]);
        }
    }
}