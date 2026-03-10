export type User = {
  id: number;
  name: string;
  email?: string;
  created_at?: string;
};

export type Message = {
  id: number;
  sender_id: number;
  receiver_id: number;
  content: string;
  created_at: string;
};

export type Conversation = {
  user: User;
  last_message: {
    id: number;
    content: string;
    is_read: boolean;
    created_at: string;
  };
  unread_count: number;
};
