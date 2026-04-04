import { Component, OnInit, OnDestroy, signal, ViewChild, ElementRef, AfterViewChecked } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonMenuButton,
  IonButtons,
  IonButton,
  IonIcon,
  IonSpinner,
  IonBadge,
  IonList,
  IonItem,
  IonLabel,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import {
  sparklesOutline,
  addOutline,
  sendOutline,
  trashOutline,
  chatbubblesOutline,
  buildOutline,
  checkmarkCircleOutline,
  alertCircleOutline,
  personOutline,
  closeOutline,
} from 'ionicons/icons';
import { ChatService, ChatConversation, ChatMessage } from './chat.service';

addIcons({
  'sparkles-outline': sparklesOutline,
  'add-outline': addOutline,
  'send-outline': sendOutline,
  'trash-outline': trashOutline,
  'chatbubbles-outline': chatbubblesOutline,
  'build-outline': buildOutline,
  'checkmark-circle-outline': checkmarkCircleOutline,
  'alert-circle-outline': alertCircleOutline,
  'person-outline': personOutline,
  'close-outline': closeOutline,
});

@Component({
  selector: 'app-chat-page',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    RouterLink,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonMenuButton,
    IonButtons,
    IonButton,
    IonIcon,
    IonSpinner,
    IonBadge,
    IonList,
    IonItem,
    IonLabel,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>
          <ion-icon name="sparkles-outline" style="margin-right: 8px; vertical-align: middle;"></ion-icon>
          KeepUp AI
        </ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="startNewConversation()">
            <ion-icon name="add-outline" slot="icon-only"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <div class="chat-fullpage">
        <!-- Sidebar: Conversation List -->
        <div class="chat-fullpage__sidebar">
          <div class="chat-fullpage__sidebar-header">
            <h3>Conversations</h3>
            <ion-button fill="clear" size="small" (click)="startNewConversation()">
              <ion-icon name="add-outline" slot="icon-only"></ion-icon>
            </ion-button>
          </div>
          <div class="chat-fullpage__conv-list">
            @if (loadingConversations()) {
              <div class="chat-fullpage__sidebar-loading">
                <ion-spinner name="crescent"></ion-spinner>
              </div>
            } @else if (conversations().length === 0) {
              <div class="chat-fullpage__sidebar-empty">
                <p>No conversations yet.</p>
              </div>
            } @else {
              @for (conv of conversations(); track conv.id) {
                <div
                  class="chat-fullpage__conv-item"
                  [class.chat-fullpage__conv-item--active]="conv.id === activeConversationId()"
                  (click)="switchConversation(conv)"
                >
                  <div class="chat-fullpage__conv-title">{{ conv.title }}</div>
                  <div class="chat-fullpage__conv-meta">
                    {{ conv.message_count }} msgs &middot; {{ formatDate(conv.created) }}
                  </div>
                  <button class="chat-fullpage__conv-delete" (click)="deleteConversation(conv, $event)">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              }
            }
          </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-fullpage__main">
          <div class="chat-fullpage__messages" #messageContainer>
            @if (loadingConversation()) {
              <div class="chat-fullpage__msg-loading">
                <ion-spinner name="crescent"></ion-spinner>
              </div>
            } @else if (messages().length === 0 && !sending()) {
              <div class="chat-fullpage__empty">
                <ion-icon name="sparkles-outline" class="chat-fullpage__empty-icon"></ion-icon>
                <h2>How can I help you today?</h2>
                <p>Ask about your monitors, incidents, alerts, or get help configuring KeepUp.</p>
                <div class="chat-fullpage__suggestions">
                  @for (suggestion of suggestions; track suggestion) {
                    <button class="chat-fullpage__suggestion" (click)="sendSuggestion(suggestion)">
                      {{ suggestion }}
                    </button>
                  }
                </div>
              </div>
            } @else {
              @for (msg of messages(); track msg.id) {
                <div
                  class="cfp-msg"
                  [class.cfp-msg--user]="msg.role === 'user'"
                  [class.cfp-msg--assistant]="msg.role === 'assistant'"
                >
                  @if (msg.role === 'assistant') {
                    <div class="cfp-msg__avatar cfp-msg__avatar--ai">
                      <ion-icon name="sparkles-outline"></ion-icon>
                    </div>
                  }
                  <div class="cfp-msg__bubble">
                    @if (msg.tool_calls && msg.tool_calls.length > 0) {
                      <div class="cfp-msg__tools">
                        @for (tool of msg.tool_calls; track $index) {
                          <span class="cfp-msg__tool">
                            <ion-icon name="build-outline"></ion-icon>
                            {{ formatToolName(tool.name) }}
                            @if (msg.tool_results && msg.tool_results[$index]) {
                              <ion-icon
                                [name]="msg.tool_results[$index].error ? 'alert-circle-outline' : 'checkmark-circle-outline'"
                                [style.color]="msg.tool_results[$index].error ? '#E53935' : '#43A047'"
                              ></ion-icon>
                            }
                          </span>
                        }
                      </div>
                    }
                    <div class="cfp-msg__content" [innerHTML]="formatContent(msg.content)"></div>
                    <div class="cfp-msg__time">{{ formatTime(msg.created) }}</div>
                  </div>
                  @if (msg.role === 'user') {
                    <div class="cfp-msg__avatar cfp-msg__avatar--user">
                      <ion-icon name="person-outline"></ion-icon>
                    </div>
                  }
                </div>
              }
              @if (sending()) {
                <div class="cfp-msg cfp-msg--assistant">
                  <div class="cfp-msg__avatar cfp-msg__avatar--ai">
                    <ion-icon name="sparkles-outline"></ion-icon>
                  </div>
                  <div class="cfp-msg__bubble">
                    <div class="cfp-msg__thinking">
                      <ion-spinner name="dots"></ion-spinner>
                      <span>Thinking...</span>
                    </div>
                  </div>
                </div>
              }
            }
          </div>

          @if (error()) {
            <div class="chat-fullpage__error">
              <ion-icon name="alert-circle-outline"></ion-icon>
              {{ error() }}
              <button (click)="clearError()"><ion-icon name="close-outline"></ion-icon></button>
            </div>
          }

          <div class="chat-fullpage__input">
            <textarea
              class="chat-fullpage__textarea"
              [(ngModel)]="inputText"
              (keydown)="onKeydown($event)"
              placeholder="Ask KeepUp AI..."
              [disabled]="sending()"
              rows="1"
              #inputField
            ></textarea>
            <button
              class="chat-fullpage__send"
              [disabled]="sending() || !inputText.trim()"
              (click)="send()"
            >
              <ion-icon name="send-outline"></ion-icon>
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  `,
  styles: [`
    .chat-fullpage {
      display: flex;
      height: 100%;
    }

    /* Sidebar */
    .chat-fullpage__sidebar {
      width: 280px;
      border-right: 1px solid var(--ion-border-color, #e0e0e0);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      background: var(--ion-color-light, #f4f5f8);
    }
    .chat-fullpage__sidebar-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border-bottom: 1px solid var(--ion-border-color, #e0e0e0);
    }
    .chat-fullpage__sidebar-header h3 {
      margin: 0;
      font-size: 15px;
      font-weight: 600;
    }
    .chat-fullpage__conv-list {
      flex: 1;
      overflow-y: auto;
    }
    .chat-fullpage__sidebar-loading,
    .chat-fullpage__sidebar-empty {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px;
      color: var(--ion-color-medium, #999);
      font-size: 13px;
    }
    .chat-fullpage__conv-item {
      padding: 12px 16px;
      cursor: pointer;
      border-bottom: 1px solid var(--ion-border-color, #f0f0f0);
      position: relative;
      transition: background 0.15s;
    }
    .chat-fullpage__conv-item:hover {
      background: rgba(0, 0, 0, 0.04);
    }
    .chat-fullpage__conv-item--active {
      background: rgba(41, 121, 255, 0.1);
      border-left: 3px solid #2979FF;
    }
    .chat-fullpage__conv-title {
      font-size: 13px;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      padding-right: 28px;
    }
    .chat-fullpage__conv-meta {
      font-size: 11px;
      color: var(--ion-color-medium, #999);
      margin-top: 2px;
    }
    .chat-fullpage__conv-delete {
      position: absolute;
      top: 50%;
      right: 8px;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      cursor: pointer;
      color: var(--ion-color-medium, #999);
      opacity: 0;
      transition: opacity 0.15s;
      width: 26px;
      height: 26px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .chat-fullpage__conv-item:hover .chat-fullpage__conv-delete {
      opacity: 1;
    }
    .chat-fullpage__conv-delete:hover {
      color: #E53935;
      background: rgba(229, 57, 53, 0.1);
    }

    /* Main area */
    .chat-fullpage__main {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .chat-fullpage__messages {
      flex: 1;
      overflow-y: auto;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .chat-fullpage__msg-loading {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
    }

    /* Empty state */
    .chat-fullpage__empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      text-align: center;
    }
    .chat-fullpage__empty-icon {
      font-size: 56px;
      color: #2979FF;
      margin-bottom: 16px;
    }
    .chat-fullpage__empty h2 {
      font-size: 22px;
      margin: 0 0 8px;
    }
    .chat-fullpage__empty p {
      color: var(--ion-color-medium, #888);
      margin: 0 0 24px;
      max-width: 480px;
    }
    .chat-fullpage__suggestions {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      max-width: 520px;
    }
    .chat-fullpage__suggestion {
      background: var(--ion-color-light, #f4f5f8);
      border: 1px solid var(--ion-border-color, #e0e0e0);
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 13px;
      cursor: pointer;
      text-align: left;
      transition: background 0.15s, border-color 0.15s;
    }
    .chat-fullpage__suggestion:hover {
      background: rgba(41, 121, 255, 0.08);
      border-color: #2979FF;
    }

    /* Messages */
    .cfp-msg {
      display: flex;
      align-items: flex-end;
      gap: 10px;
      max-width: 720px;
    }
    .cfp-msg--user {
      align-self: flex-end;
    }
    .cfp-msg--assistant {
      align-self: flex-start;
    }
    .cfp-msg__avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 16px;
    }
    .cfp-msg__avatar--ai {
      background: linear-gradient(135deg, #1A2332, #2979FF);
      color: #fff;
    }
    .cfp-msg__avatar--user {
      background: #2979FF;
      color: #fff;
    }
    .cfp-msg__bubble {
      border-radius: 16px;
      padding: 12px 16px;
      font-size: 14px;
      line-height: 1.6;
      word-break: break-word;
    }
    .cfp-msg--user .cfp-msg__bubble {
      background: #2979FF;
      color: #fff;
      border-bottom-right-radius: 4px;
    }
    .cfp-msg--assistant .cfp-msg__bubble {
      background: var(--ion-color-light, #f4f5f8);
      color: var(--ion-text-color, #333);
      border-bottom-left-radius: 4px;
    }
    .cfp-msg__tools {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 8px;
    }
    .cfp-msg__tool {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      color: var(--ion-color-medium, #888);
      background: rgba(0, 0, 0, 0.04);
      padding: 3px 8px;
      border-radius: 8px;
    }
    .cfp-msg__tool ion-icon {
      font-size: 12px;
    }
    .cfp-msg__content :first-child { margin-top: 0; }
    .cfp-msg__content :last-child { margin-bottom: 0; }
    .cfp-msg__time {
      font-size: 10px;
      color: var(--ion-color-medium, #aaa);
      margin-top: 4px;
    }
    .cfp-msg--user .cfp-msg__time {
      color: rgba(255, 255, 255, 0.6);
      text-align: right;
    }
    .cfp-msg__thinking {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--ion-color-medium, #888);
      font-size: 14px;
    }
    .cfp-msg__thinking ion-spinner { width: 20px; height: 20px; }

    /* Error bar */
    .chat-fullpage__error {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: rgba(229, 57, 53, 0.08);
      color: #E53935;
      font-size: 13px;
      flex-shrink: 0;
    }
    .chat-fullpage__error button {
      background: transparent;
      border: none;
      color: #E53935;
      cursor: pointer;
      margin-left: auto;
    }

    /* Input */
    .chat-fullpage__input {
      display: flex;
      align-items: flex-end;
      padding: 12px 24px 16px;
      gap: 10px;
      border-top: 1px solid var(--ion-border-color, #e0e0e0);
      flex-shrink: 0;
    }
    .chat-fullpage__textarea {
      flex: 1;
      border: 1px solid var(--ion-border-color, #e0e0e0);
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 14px;
      line-height: 1.4;
      resize: none;
      max-height: 120px;
      background: var(--ion-color-light, #f4f5f8);
      color: var(--ion-text-color, #333);
      font-family: inherit;
      outline: none;
    }
    .chat-fullpage__textarea:focus {
      border-color: #2979FF;
    }
    .chat-fullpage__textarea:disabled {
      opacity: 0.6;
    }
    .chat-fullpage__send {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #2979FF;
      color: #fff;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .chat-fullpage__send:hover:not(:disabled) {
      background: #1565C0;
    }
    .chat-fullpage__send:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    .chat-fullpage__send ion-icon {
      font-size: 20px;
    }

    @media (max-width: 768px) {
      .chat-fullpage__sidebar {
        display: none;
      }
      .chat-fullpage__suggestions {
        grid-template-columns: 1fr;
      }
    }
  `],
})
export class ChatPageComponent implements OnInit, OnDestroy, AfterViewChecked {
  @ViewChild('messageContainer') messageContainer!: ElementRef<HTMLDivElement>;
  @ViewChild('inputField') inputField!: ElementRef<HTMLTextAreaElement>;

  conversations = signal<ChatConversation[]>([]);
  activeConversationId = signal<string | null>(null);
  messages = signal<ChatMessage[]>([]);
  sending = signal(false);
  loadingConversations = signal(false);
  loadingConversation = signal(false);
  error = signal<string | null>(null);

  inputText = '';
  private shouldScrollToBottom = false;

  suggestions = [
    'Help me set up monitoring for my website',
    'Show me my current monitor status',
    'Create a Slack notification channel',
    'Why did I get alerts last night?',
  ];

  constructor(private chatService: ChatService) {}

  ngOnInit(): void {
    this.loadConversations();
  }

  ngOnDestroy(): void {}

  ngAfterViewChecked(): void {
    if (this.shouldScrollToBottom && this.messageContainer) {
      const el = this.messageContainer.nativeElement;
      el.scrollTop = el.scrollHeight;
      this.shouldScrollToBottom = false;
    }
  }

  loadConversations(): void {
    this.loadingConversations.set(true);
    this.chatService.listConversations({ limit: 50 }).subscribe({
      next: (res) => {
        this.conversations.set(res.conversations);
        this.loadingConversations.set(false);
      },
      error: () => {
        this.loadingConversations.set(false);
      },
    });
  }

  startNewConversation(): void {
    this.activeConversationId.set(null);
    this.messages.set([]);
    this.error.set(null);
    this.inputText = '';
  }

  switchConversation(conv: ChatConversation): void {
    this.activeConversationId.set(conv.id);
    this.loadConversation(conv.id);
  }

  loadConversation(id: string): void {
    this.loadingConversation.set(true);
    this.error.set(null);
    this.chatService.getConversation(id).subscribe({
      next: (res) => {
        this.messages.set(res.messages);
        this.activeConversationId.set(res.conversation.id);
        this.loadingConversation.set(false);
        this.shouldScrollToBottom = true;
      },
      error: (err) => {
        this.error.set(err.message || 'Failed to load conversation.');
        this.loadingConversation.set(false);
      },
    });
  }

  deleteConversation(conv: ChatConversation, event: Event): void {
    event.stopPropagation();
    this.chatService.deleteConversation(conv.id).subscribe({
      next: () => {
        this.conversations.update((list) => list.filter((c) => c.id !== conv.id));
        if (this.activeConversationId() === conv.id) {
          this.startNewConversation();
        }
      },
      error: (err) => {
        this.error.set(err.message || 'Failed to delete conversation.');
      },
    });
  }

  sendSuggestion(text: string): void {
    this.inputText = text;
    this.send();
  }

  send(): void {
    const text = this.inputText.trim();
    if (!text || this.sending()) return;

    this.error.set(null);
    this.sending.set(true);
    this.inputText = '';

    const tempUserMsg: ChatMessage = {
      id: Date.now(),
      role: 'user',
      content: text,
      created: new Date().toISOString(),
    };
    this.messages.update((msgs) => [...msgs, tempUserMsg]);
    this.shouldScrollToBottom = true;

    if (!this.activeConversationId()) {
      this.chatService.createConversation(text.substring(0, 80)).subscribe({
        next: (res) => {
          this.activeConversationId.set(res.conversation.id);
          this.conversations.update((list) => [res.conversation, ...list]);
          this.doSendMessage(res.conversation.id, text);
        },
        error: (err) => this.handleSendError(err),
      });
    } else {
      this.doSendMessage(this.activeConversationId()!, text);
    }
  }

  private doSendMessage(conversationId: string, text: string): void {
    this.chatService.sendMessage(conversationId, text).subscribe({
      next: (res) => {
        this.messages.update((msgs) => [...msgs, res.message]);
        this.sending.set(false);
        this.shouldScrollToBottom = true;
        this.conversations.update((list) =>
          list.map((c) =>
            c.id === conversationId ? { ...c, message_count: c.message_count + 2 } : c,
          ),
        );
      },
      error: (err) => this.handleSendError(err),
    });
  }

  private handleSendError(err: any): void {
    this.sending.set(false);
    if (err.status === 429) {
      this.error.set('Daily message limit reached. Try again tomorrow.');
    } else if (err.status === 403 || err.error_type === 'plan_limit') {
      this.error.set('AI Chat is not available on your current plan. Please upgrade.');
    } else {
      this.error.set(err.message || 'Failed to send message.');
    }
  }

  clearError(): void {
    this.error.set(null);
  }

  onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      this.send();
    }
  }

  formatContent(content: string): string {
    if (!content) return '';
    let html = this.escapeHtml(content);
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    html = html.replace(
      /`([^`]+)`/g,
      '<code style="background:rgba(0,0,0,0.06);padding:1px 4px;border-radius:3px;font-size:12px;">$1</code>',
    );
    html = html.replace(
      /```(\w*)\n?([\s\S]*?)```/g,
      '<pre style="background:rgba(0,0,0,0.06);padding:8px;border-radius:6px;overflow-x:auto;font-size:12px;margin:4px 0;"><code>$2</code></pre>',
    );
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  private escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  formatToolName(name: string): string {
    if (!name) return 'Running tool...';
    return name.replace(/_/g, ' ').replace(/([a-z])([A-Z])/g, '$1 $2').replace(/\b\w/g, (c) => c.toUpperCase());
  }

  formatTime(dateStr: string): string {
    if (!dateStr) return '';
    try {
      return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch { return ''; }
  }

  formatDate(dateStr: string): string {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      const now = new Date();
      const diffMin = Math.floor((now.getTime() - date.getTime()) / 60000);
      if (diffMin < 60) return `${diffMin}m ago`;
      const diffHr = Math.floor(diffMin / 60);
      if (diffHr < 24) return `${diffHr}h ago`;
      return `${Math.floor(diffHr / 24)}d ago`;
    } catch { return ''; }
  }
}
