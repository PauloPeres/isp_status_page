import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../core/services/api.service';

export interface ChatConversation {
  id: string; // public_id
  title: string;
  status: string;
  message_count: number;
  input_tokens_used?: number;
  output_tokens_used?: number;
  created: string;
  modified?: string;
}

export interface ChatMessage {
  id: number;
  role: 'user' | 'assistant' | 'system';
  content: string;
  tool_calls?: ToolCall[] | null;
  tool_results?: ToolResult[] | null;
  input_tokens?: number;
  output_tokens?: number;
  created: string;
}

export interface ToolCall {
  name: string;
  arguments?: Record<string, any>;
}

export interface ToolResult {
  name: string;
  result?: any;
  error?: string;
}

export interface CreateConversationResponse {
  conversation: ChatConversation;
}

export interface ListConversationsResponse {
  conversations: ChatConversation[];
  page: number;
  limit: number;
}

export interface ViewConversationResponse {
  conversation: ChatConversation;
  messages: ChatMessage[];
}

export interface SendMessageResponse {
  message: ChatMessage;
  conversation_id: string;
  tokens?: { input: number; output: number } | null;
  tool_calls_count: number;
}

@Injectable({ providedIn: 'root' })
export class ChatService {
  constructor(private api: ApiService) {}

  createConversation(title?: string): Observable<CreateConversationResponse> {
    return this.api.post<CreateConversationResponse>('/chat/conversations', {
      title: title || 'New conversation',
    });
  }

  listConversations(
    params: { status?: string; page?: number; limit?: number } = {},
  ): Observable<ListConversationsResponse> {
    return this.api.get<ListConversationsResponse>(
      '/chat/conversations',
      params,
    );
  }

  getConversation(id: string): Observable<ViewConversationResponse> {
    return this.api.get<ViewConversationResponse>(
      `/chat/conversations/${id}`,
    );
  }

  deleteConversation(id: string): Observable<{ message: string }> {
    return this.api.delete<{ message: string }>(
      `/chat/conversations/${id}`,
    );
  }

  sendMessage(
    conversationId: string,
    message: string,
  ): Observable<SendMessageResponse> {
    return this.api.post<SendMessageResponse>(
      `/chat/conversations/${conversationId}/messages`,
      { message },
    );
  }
}
