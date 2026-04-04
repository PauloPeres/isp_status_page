import {
  Component,
  OnInit,
  OnDestroy,
  signal,
  computed,
  ViewChild,
  ElementRef,
  AfterViewChecked,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonButton,
  IonIcon,
  IonSpinner,
  IonBadge,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import {
  sparklesOutline,
  closeOutline,
  addOutline,
  sendOutline,
  trashOutline,
  chatbubblesOutline,
  chevronDownOutline,
  buildOutline,
  checkmarkCircleOutline,
  alertCircleOutline,
  personOutline,
} from 'ionicons/icons';
import {
  ChatService,
  ChatConversation,
  ChatMessage,
  SendMessageResponse,
} from './chat.service';

addIcons({
  'sparkles-outline': sparklesOutline,
  'close-outline': closeOutline,
  'add-outline': addOutline,
  'send-outline': sendOutline,
  'trash-outline': trashOutline,
  'chatbubbles-outline': chatbubblesOutline,
  'chevron-down-outline': chevronDownOutline,
  'build-outline': buildOutline,
  'checkmark-circle-outline': checkmarkCircleOutline,
  'alert-circle-outline': alertCircleOutline,
  'person-outline': personOutline,
});

@Component({
  selector: 'app-chat-widget',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    IonButton,
    IonIcon,
    IonSpinner,
    IonBadge,
  ],
  template: `
    <!-- Floating Action Button -->
    @if (!isOpen()) {
      <button class="chat-fab" (click)="open()" title="KeepUp AI Assistant">
        <ion-icon name="sparkles-outline"></ion-icon>
        <span class="chat-fab__badge">AI</span>
      </button>
    }

    <!-- Chat Panel -->
    @if (isOpen()) {
      <div class="chat-panel" [class.chat-panel--animating]="isAnimating()">

        <!-- Header -->
        <div class="chat-header">
          <div class="chat-header__left">
            <ion-icon name="sparkles-outline" class="chat-header__icon"></ion-icon>
            <span class="chat-header__title">KeepUp AI</span>
          </div>

          <div class="chat-header__actions">
            <!-- Conversation Selector -->
            @if (conversations().length > 0) {
              <div class="chat-header__selector">
                <button
                  class="chat-header__selector-btn"
                  (click)="toggleConversationList()"
                  title="Switch conversation"
                >
                  <ion-icon name="chatbubbles-outline"></ion-icon>
                  <ion-icon name="chevron-down-outline" class="chat-header__chevron"></ion-icon>
                </button>
              </div>
            }

            <button class="chat-header__action-btn" (click)="startNewConversation()" title="New conversation">
              <ion-icon name="add-outline"></ion-icon>
            </button>

            <button class="chat-header__action-btn" (click)="close()" title="Close">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
        </div>

        <!-- Conversation List Dropdown -->
        @if (showConversationList()) {
          <div class="chat-conv-list">
            @for (conv of conversations(); track conv.id) {
              <div
                class="chat-conv-list__item"
                [class.chat-conv-list__item--active]="conv.id === activeConversationId()"
                (click)="switchConversation(conv)"
              >
                <div class="chat-conv-list__title">{{ conv.title }}</div>
                <div class="chat-conv-list__meta">
                  {{ conv.message_count }} messages &middot; {{ formatDate(conv.created) }}
                </div>
                <button
                  class="chat-conv-list__delete"
                  (click)="deleteConversation(conv, $event)"
                  title="Delete conversation"
                >
                  <ion-icon name="trash-outline"></ion-icon>
                </button>
              </div>
            }
          </div>
        }

        <!-- Message Area -->
        <div class="chat-messages" #messageContainer>
          @if (loadingConversation()) {
            <div class="chat-messages__loading">
              <ion-spinner name="crescent"></ion-spinner>
            </div>
          } @else if (messages().length === 0 && !sending()) {
            <!-- Empty state with suggestions -->
            <div class="chat-empty">
              <ion-icon name="sparkles-outline" class="chat-empty__icon"></ion-icon>
              <h3 class="chat-empty__title">How can I help?</h3>
              <p class="chat-empty__desc">Ask me anything about your monitoring setup, incidents, or configurations.</p>
              <div class="chat-suggestions">
                @for (suggestion of suggestions; track suggestion) {
                  <button class="chat-suggestion" (click)="sendSuggestion(suggestion)">
                    {{ suggestion }}
                  </button>
                }
              </div>
            </div>
          } @else {
            @for (msg of messages(); track msg.id) {
              <div
                class="chat-msg"
                [class.chat-msg--user]="msg.role === 'user'"
                [class.chat-msg--assistant]="msg.role === 'assistant'"
                [class.chat-msg--system]="msg.role === 'system'"
              >
                @if (msg.role === 'assistant') {
                  <div class="chat-msg__avatar chat-msg__avatar--ai">
                    <ion-icon name="sparkles-outline"></ion-icon>
                  </div>
                }
                <div class="chat-msg__bubble">
                  <!-- Tool calls indicator -->
                  @if (msg.tool_calls && msg.tool_calls.length > 0) {
                    <div class="chat-msg__tools">
                      @for (tool of msg.tool_calls; track $index) {
                        <div class="chat-msg__tool">
                          <ion-icon name="build-outline" class="chat-msg__tool-icon"></ion-icon>
                          <span class="chat-msg__tool-name">{{ formatToolName(tool.name) }}</span>
                          @if (msg.tool_results && msg.tool_results[$index]) {
                            <ion-icon
                              [name]="msg.tool_results[$index].error ? 'alert-circle-outline' : 'checkmark-circle-outline'"
                              [class.chat-msg__tool-ok]="!msg.tool_results[$index].error"
                              [class.chat-msg__tool-err]="msg.tool_results[$index].error"
                            ></ion-icon>
                          }
                        </div>
                      }
                    </div>
                  }
                  <div class="chat-msg__content" [innerHTML]="formatContent(msg.content)"></div>
                  <div class="chat-msg__time">{{ formatTime(msg.created) }}</div>
                </div>
                @if (msg.role === 'user') {
                  <div class="chat-msg__avatar chat-msg__avatar--user">
                    <ion-icon name="person-outline"></ion-icon>
                  </div>
                }
              </div>
            }

            <!-- Sending indicator -->
            @if (sending()) {
              <div class="chat-msg chat-msg--assistant">
                <div class="chat-msg__avatar chat-msg__avatar--ai">
                  <ion-icon name="sparkles-outline"></ion-icon>
                </div>
                <div class="chat-msg__bubble chat-msg__bubble--thinking">
                  <div class="chat-msg__thinking">
                    <ion-spinner name="dots"></ion-spinner>
                    <span>Thinking...</span>
                  </div>
                </div>
              </div>
            }
          }
        </div>

        <!-- Error Banner -->
        @if (error()) {
          <div class="chat-error">
            <ion-icon name="alert-circle-outline"></ion-icon>
            <span>{{ error() }}</span>
            <button class="chat-error__close" (click)="clearError()">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
        }

        <!-- Input Area -->
        <div class="chat-input">
          <textarea
            class="chat-input__field"
            [(ngModel)]="inputText"
            (keydown)="onKeydown($event)"
            placeholder="Ask KeepUp AI..."
            [disabled]="sending()"
            rows="1"
            #inputField
          ></textarea>
          <button
            class="chat-input__send"
            [disabled]="sending() || !inputText.trim()"
            (click)="send()"
            title="Send message"
          >
            <ion-icon name="send-outline"></ion-icon>
          </button>
        </div>

      </div>
    }
  `,
  styles: [`
    /* === Floating Action Button === */
    .chat-fab {
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1A2332 0%, #2979FF 100%);
      color: #fff;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 20px rgba(41, 121, 255, 0.4);
      transition: transform 0.2s, box-shadow 0.2s;
      z-index: 10000;
    }
    .chat-fab:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 28px rgba(41, 121, 255, 0.5);
    }
    .chat-fab ion-icon {
      font-size: 26px;
    }
    .chat-fab__badge {
      position: absolute;
      top: -2px;
      right: -2px;
      background: #2979FF;
      color: #fff;
      font-size: 9px;
      font-weight: 700;
      padding: 2px 5px;
      border-radius: 8px;
      letter-spacing: 0.5px;
    }

    /* === Chat Panel === */
    .chat-panel {
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 380px;
      height: 550px;
      background: var(--ion-background-color, #ffffff);
      border-radius: 16px;
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      z-index: 10001;
      animation: chatSlideUp 0.3s ease-out;
    }
    .chat-panel--animating {
      animation: chatSlideDown 0.2s ease-in forwards;
    }

    @keyframes chatSlideUp {
      from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    @keyframes chatSlideDown {
      from {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
      to {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
      }
    }

    /* === Header === */
    .chat-header {
      background: #1A2332;
      color: #fff;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
    .chat-header__left {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .chat-header__icon {
      font-size: 20px;
      color: #2979FF;
    }
    .chat-header__title {
      font-size: 15px;
      font-weight: 600;
    }
    .chat-header__actions {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .chat-header__selector {
      position: relative;
    }
    .chat-header__selector-btn,
    .chat-header__action-btn {
      background: transparent;
      border: none;
      color: rgba(255, 255, 255, 0.8);
      cursor: pointer;
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s, color 0.15s;
    }
    .chat-header__selector-btn {
      width: auto;
      padding: 0 8px;
      gap: 2px;
    }
    .chat-header__selector-btn:hover,
    .chat-header__action-btn:hover {
      background: rgba(255, 255, 255, 0.12);
      color: #fff;
    }
    .chat-header__chevron {
      font-size: 12px;
    }

    /* === Conversation List === */
    .chat-conv-list {
      background: var(--ion-background-color, #fff);
      border-bottom: 1px solid var(--ion-border-color, #e0e0e0);
      max-height: 200px;
      overflow-y: auto;
      flex-shrink: 0;
    }
    .chat-conv-list__item {
      padding: 10px 16px;
      cursor: pointer;
      position: relative;
      border-bottom: 1px solid var(--ion-border-color, #f0f0f0);
      transition: background 0.15s;
    }
    .chat-conv-list__item:hover {
      background: var(--ion-color-light, #f4f5f8);
    }
    .chat-conv-list__item--active {
      background: rgba(41, 121, 255, 0.08);
      border-left: 3px solid #2979FF;
    }
    .chat-conv-list__title {
      font-size: 13px;
      font-weight: 500;
      color: var(--ion-text-color, #333);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      padding-right: 30px;
    }
    .chat-conv-list__meta {
      font-size: 11px;
      color: var(--ion-color-medium, #999);
      margin-top: 2px;
    }
    .chat-conv-list__delete {
      position: absolute;
      top: 50%;
      right: 8px;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: var(--ion-color-medium, #999);
      cursor: pointer;
      width: 28px;
      height: 28px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.15s, background 0.15s, color 0.15s;
    }
    .chat-conv-list__item:hover .chat-conv-list__delete {
      opacity: 1;
    }
    .chat-conv-list__delete:hover {
      background: rgba(229, 57, 53, 0.1);
      color: #E53935;
    }

    /* === Messages Area === */
    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 16px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .chat-messages__loading {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
    }

    /* === Empty State === */
    .chat-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      text-align: center;
      padding: 0 16px;
    }
    .chat-empty__icon {
      font-size: 48px;
      color: #2979FF;
      margin-bottom: 12px;
    }
    .chat-empty__title {
      font-size: 18px;
      font-weight: 600;
      color: var(--ion-text-color, #333);
      margin: 0 0 6px;
    }
    .chat-empty__desc {
      font-size: 13px;
      color: var(--ion-color-medium, #888);
      margin: 0 0 20px;
      line-height: 1.4;
    }

    /* === Suggestions === */
    .chat-suggestions {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }
    .chat-suggestion {
      background: var(--ion-color-light, #f4f5f8);
      color: var(--ion-text-color, #333);
      border: 1px solid var(--ion-border-color, #e0e0e0);
      border-radius: 12px;
      padding: 10px 14px;
      font-size: 13px;
      cursor: pointer;
      text-align: left;
      transition: background 0.15s, border-color 0.15s;
    }
    .chat-suggestion:hover {
      background: rgba(41, 121, 255, 0.08);
      border-color: #2979FF;
    }

    /* === Message Bubbles === */
    .chat-msg {
      display: flex;
      align-items: flex-end;
      gap: 8px;
      max-width: 92%;
    }
    .chat-msg--user {
      align-self: flex-end;
      flex-direction: row;
    }
    .chat-msg--assistant,
    .chat-msg--system {
      align-self: flex-start;
    }
    .chat-msg__avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 14px;
    }
    .chat-msg__avatar--ai {
      background: linear-gradient(135deg, #1A2332, #2979FF);
      color: #fff;
    }
    .chat-msg__avatar--user {
      background: #2979FF;
      color: #fff;
    }
    .chat-msg__bubble {
      border-radius: 16px;
      padding: 10px 14px;
      font-size: 13px;
      line-height: 1.5;
      word-break: break-word;
    }
    .chat-msg--user .chat-msg__bubble {
      background: #2979FF;
      color: #fff;
      border-bottom-right-radius: 4px;
    }
    .chat-msg--assistant .chat-msg__bubble {
      background: var(--ion-color-light, #f4f5f8);
      color: var(--ion-text-color, #333);
      border-bottom-left-radius: 4px;
    }
    .chat-msg--system .chat-msg__bubble {
      background: rgba(41, 121, 255, 0.06);
      color: var(--ion-color-medium, #888);
      font-size: 12px;
      font-style: italic;
    }
    .chat-msg__bubble--thinking {
      min-width: 120px;
    }

    /* === Tool Calls === */
    .chat-msg__tools {
      margin-bottom: 8px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .chat-msg__tool {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      color: var(--ion-color-medium, #888);
      padding: 4px 8px;
      background: rgba(0, 0, 0, 0.04);
      border-radius: 8px;
    }
    .chat-msg__tool-icon {
      font-size: 12px;
      color: #2979FF;
    }
    .chat-msg__tool-name {
      flex: 1;
    }
    .chat-msg__tool-ok {
      color: #43A047;
      font-size: 13px;
    }
    .chat-msg__tool-err {
      color: #E53935;
      font-size: 13px;
    }

    /* === Content formatting === */
    .chat-msg__content :first-child {
      margin-top: 0;
    }
    .chat-msg__content :last-child {
      margin-bottom: 0;
    }

    .chat-msg__time {
      font-size: 10px;
      color: var(--ion-color-medium, #aaa);
      margin-top: 4px;
    }
    .chat-msg--user .chat-msg__time {
      color: rgba(255, 255, 255, 0.6);
      text-align: right;
    }

    .chat-msg__thinking {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--ion-color-medium, #888);
      font-size: 13px;
    }
    .chat-msg__thinking ion-spinner {
      width: 18px;
      height: 18px;
    }

    /* === Error Banner === */
    .chat-error {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      background: rgba(229, 57, 53, 0.08);
      color: #E53935;
      font-size: 12px;
      flex-shrink: 0;
    }
    .chat-error ion-icon {
      font-size: 16px;
      flex-shrink: 0;
    }
    .chat-error span {
      flex: 1;
    }
    .chat-error__close {
      background: transparent;
      border: none;
      color: #E53935;
      cursor: pointer;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
    }
    .chat-error__close:hover {
      background: rgba(229, 57, 53, 0.12);
    }

    /* === Input Area === */
    .chat-input {
      display: flex;
      align-items: flex-end;
      padding: 10px 12px;
      border-top: 1px solid var(--ion-border-color, #e0e0e0);
      background: var(--ion-background-color, #fff);
      gap: 8px;
      flex-shrink: 0;
    }
    .chat-input__field {
      flex: 1;
      border: 1px solid var(--ion-border-color, #e0e0e0);
      border-radius: 12px;
      padding: 10px 14px;
      font-size: 13px;
      line-height: 1.4;
      resize: none;
      max-height: 100px;
      background: var(--ion-color-light, #f4f5f8);
      color: var(--ion-text-color, #333);
      font-family: inherit;
      outline: none;
      transition: border-color 0.15s;
    }
    .chat-input__field:focus {
      border-color: #2979FF;
    }
    .chat-input__field::placeholder {
      color: var(--ion-color-medium, #999);
    }
    .chat-input__field:disabled {
      opacity: 0.6;
    }
    .chat-input__send {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: #2979FF;
      color: #fff;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: background 0.15s, opacity 0.15s;
    }
    .chat-input__send:hover:not(:disabled) {
      background: #1565C0;
    }
    .chat-input__send:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    .chat-input__send ion-icon {
      font-size: 18px;
    }

    /* === Responsive === */
    @media (max-width: 480px) {
      .chat-panel {
        bottom: 0;
        right: 0;
        width: 100%;
        height: 100%;
        border-radius: 0;
      }
      .chat-fab {
        bottom: 16px;
        right: 16px;
      }
    }

    /* === Dark mode overrides === */
    :host-context(.dark) .chat-msg--assistant .chat-msg__bubble,
    :host-context(body.dark) .chat-msg--assistant .chat-msg__bubble {
      background: rgba(255, 255, 255, 0.08);
    }
    :host-context(.dark) .chat-suggestion,
    :host-context(body.dark) .chat-suggestion {
      background: rgba(255, 255, 255, 0.06);
      border-color: rgba(255, 255, 255, 0.12);
    }
    :host-context(.dark) .chat-suggestion:hover,
    :host-context(body.dark) .chat-suggestion:hover {
      background: rgba(41, 121, 255, 0.15);
    }
    :host-context(.dark) .chat-input__field,
    :host-context(body.dark) .chat-input__field {
      background: rgba(255, 255, 255, 0.06);
      border-color: rgba(255, 255, 255, 0.12);
    }
    :host-context(.dark) .chat-msg__tool,
    :host-context(body.dark) .chat-msg__tool {
      background: rgba(255, 255, 255, 0.06);
    }
    :host-context(.dark) .chat-panel,
    :host-context(body.dark) .chat-panel {
      background: var(--ion-background-color, #1e1e1e);
    }
    :host-context(.dark) .chat-conv-list__item:hover,
    :host-context(body.dark) .chat-conv-list__item:hover {
      background: rgba(255, 255, 255, 0.06);
    }

    /* Ionic dark mode variable-based support */
    @media (prefers-color-scheme: dark) {
      :host-context(body:not(.light)) .chat-msg--assistant .chat-msg__bubble {
        background: rgba(255, 255, 255, 0.08);
      }
      :host-context(body:not(.light)) .chat-input__field {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.12);
      }
    }
  `],
})
export class ChatWidgetComponent implements OnInit, OnDestroy, AfterViewChecked {
  @ViewChild('messageContainer') messageContainer!: ElementRef<HTMLDivElement>;
  @ViewChild('inputField') inputField!: ElementRef<HTMLTextAreaElement>;

  isOpen = signal(false);
  isAnimating = signal(false);
  showConversationList = signal(false);
  conversations = signal<ChatConversation[]>([]);
  activeConversationId = signal<string | null>(null);
  messages = signal<ChatMessage[]>([]);
  sending = signal(false);
  loadingConversation = signal(false);
  error = signal<string | null>(null);

  inputText = '';
  private shouldScrollToBottom = false;
  private autoResizeObserver: ResizeObserver | null = null;

  suggestions = [
    'Help me set up monitoring for my website',
    'Show me my current monitor status',
    'Create a Slack notification channel',
    'Why did I get alerts last night?',
  ];

  constructor(
    private chatService: ChatService,
    private cdr: ChangeDetectorRef,
  ) {}

  ngOnInit(): void {
    // Load conversations in background
  }

  ngOnDestroy(): void {
    this.autoResizeObserver?.disconnect();
  }

  ngAfterViewChecked(): void {
    if (this.shouldScrollToBottom && this.messageContainer) {
      this.scrollToBottom();
      this.shouldScrollToBottom = false;
    }
  }

  open(): void {
    this.isOpen.set(true);
    this.isAnimating.set(false);
    this.loadConversations();

    setTimeout(() => {
      this.inputField?.nativeElement?.focus();
    }, 300);
  }

  close(): void {
    this.isAnimating.set(true);
    this.showConversationList.set(false);
    setTimeout(() => {
      this.isOpen.set(false);
      this.isAnimating.set(false);
    }, 200);
  }

  toggleConversationList(): void {
    this.showConversationList.update((v) => !v);
  }

  loadConversations(): void {
    this.chatService.listConversations({ limit: 20 }).subscribe({
      next: (res) => {
        this.conversations.set(res.conversations);
      },
      error: (err) => {
        // Silently fail - conversations list is not critical
        if (err.status === 403 || err.error_type === 'plan_limit') {
          // Plan doesn't support AI chat - hide widget behavior handled by parent
        }
      },
    });
  }

  startNewConversation(): void {
    this.showConversationList.set(false);
    this.activeConversationId.set(null);
    this.messages.set([]);
    this.error.set(null);
    this.inputText = '';
    setTimeout(() => {
      this.inputField?.nativeElement?.focus();
    }, 100);
  }

  switchConversation(conv: ChatConversation): void {
    this.showConversationList.set(false);
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
        this.conversations.update((list) =>
          list.filter((c) => c.id !== conv.id),
        );
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

    // Optimistically add user message
    const tempUserMsg: ChatMessage = {
      id: Date.now(),
      role: 'user',
      content: text,
      created: new Date().toISOString(),
    };
    this.messages.update((msgs) => [...msgs, tempUserMsg]);
    this.shouldScrollToBottom = true;

    // If no active conversation, create one first
    if (!this.activeConversationId()) {
      this.chatService
        .createConversation(text.substring(0, 80))
        .subscribe({
          next: (res) => {
            this.activeConversationId.set(res.conversation.id);
            this.conversations.update((list) => [res.conversation, ...list]);
            this.doSendMessage(res.conversation.id, text);
          },
          error: (err) => {
            this.handleSendError(err);
          },
        });
    } else {
      this.doSendMessage(this.activeConversationId()!, text);
    }
  }

  private doSendMessage(conversationId: string, text: string): void {
    this.chatService.sendMessage(conversationId, text).subscribe({
      next: (res) => {
        // Add assistant response
        this.messages.update((msgs) => [...msgs, res.message]);
        this.sending.set(false);
        this.shouldScrollToBottom = true;

        // Update conversation title if it was auto-generated
        this.conversations.update((list) =>
          list.map((c) =>
            c.id === conversationId
              ? { ...c, message_count: c.message_count + 2 }
              : c,
          ),
        );

        setTimeout(() => {
          this.inputField?.nativeElement?.focus();
        }, 100);
      },
      error: (err) => {
        this.handleSendError(err);
      },
    });
  }

  private handleSendError(err: any): void {
    this.sending.set(false);
    if (err.status === 429) {
      this.error.set('Daily message limit reached. Try again tomorrow.');
    } else if (err.status === 403 || err.error_type === 'plan_limit') {
      this.error.set(
        'AI Chat is not available on your current plan. Please upgrade.',
      );
    } else {
      this.error.set(err.message || 'Failed to send message. Please try again.');
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
    // Basic markdown-like formatting
    let html = this.escapeHtml(content);

    // Bold: **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Italic: *text*
    html = html.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    // Inline code: `code`
    html = html.replace(
      /`([^`]+)`/g,
      '<code style="background:rgba(0,0,0,0.06);padding:1px 4px;border-radius:3px;font-size:12px;">$1</code>',
    );
    // Code blocks: ```code```
    html = html.replace(
      /```(\w*)\n?([\s\S]*?)```/g,
      '<pre style="background:rgba(0,0,0,0.06);padding:8px;border-radius:6px;overflow-x:auto;font-size:12px;margin:4px 0;"><code>$2</code></pre>',
    );
    // Line breaks
    html = html.replace(/\n/g, '<br>');
    // Lists: lines starting with - or *
    html = html.replace(
      /^(\s*[-*])\s+/gm,
      '<span style="margin-left:4px;">&bull; </span>',
    );

    return html;
  }

  private escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  formatToolName(name: string): string {
    if (!name) return 'Running tool...';
    // Convert snake_case/camelCase to readable
    return name
      .replace(/_/g, ' ')
      .replace(/([a-z])([A-Z])/g, '$1 $2')
      .replace(/\b\w/g, (c) => c.toUpperCase());
  }

  formatTime(dateStr: string): string {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      return date.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      return '';
    }
  }

  formatDate(dateStr: string): string {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      const now = new Date();
      const diffMs = now.getTime() - date.getTime();
      const diffMin = Math.floor(diffMs / 60000);
      if (diffMin < 60) return `${diffMin}m ago`;
      const diffHr = Math.floor(diffMin / 60);
      if (diffHr < 24) return `${diffHr}h ago`;
      const diffDay = Math.floor(diffHr / 24);
      if (diffDay < 7) return `${diffDay}d ago`;
      return date.toLocaleDateString();
    } catch {
      return '';
    }
  }

  private scrollToBottom(): void {
    try {
      const el = this.messageContainer?.nativeElement;
      if (el) {
        el.scrollTop = el.scrollHeight;
      }
    } catch {}
  }
}
