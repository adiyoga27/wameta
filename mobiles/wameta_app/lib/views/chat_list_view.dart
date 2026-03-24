import 'package:flutter/material.dart';
import '../models/chat_models.dart';
import '../services/api_service.dart';
import 'chat_detail_view.dart';

class ChatListView extends StatefulWidget {
  final DeviceModel device;
  const ChatListView({super.key, required this.device});

  @override
  State<ChatListView> createState() => _ChatListViewState();
}

class _ChatListViewState extends State<ChatListView> {
  final _api = ApiService();
  late Future<List<ConversationModel>> _conversationsFuture;

  @override
  void initState() {
    super.initState();
    _conversationsFuture = _fetchConversations();
  }

  Future<List<ConversationModel>> _fetchConversations() async {
    final res = await _api.getConversations(widget.device.id);
    if (res.statusCode == 200) {
      return (res.data as List).map((e) => ConversationModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load chats');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Chats', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 22)),
            Text('${widget.device.name} • Connected', style: const TextStyle(fontSize: 12, color: Colors.white60)),
          ],
        ),
        actions: [
          IconButton(icon: const Icon(Icons.search), onPressed: () {}),
          IconButton(icon: const Icon(Icons.more_vert), onPressed: () {}),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          setState(() {
            _conversationsFuture = _fetchConversations();
          });
        },
        child: FutureBuilder<List<ConversationModel>>(
          future: _conversationsFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return Center(child: Text('Error: ${snapshot.error}'));
            }
            final chats = snapshot.data ?? [];
            if (chats.isEmpty) {
              return const Center(child: Text('Belum ada percakapan.'));
            }

            return ListView.separated(
              itemCount: chats.length,
              separatorBuilder: (_, __) => const Divider(height: 1, color: Colors.white10, indent: 80),
              itemBuilder: (context, index) {
                final chat = chats[index];
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  leading: CircleAvatar(
                    radius: 28,
                    backgroundColor: Colors.white12,
                    child: Text(chat.contactName.substring(0, 1).toUpperCase(), 
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                  title: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(child: Text(chat.contactName, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16), maxLines: 1)),
                      Text(chat.lastTime, style: TextStyle(color: chat.unreadCount > 0 ? const Color(0xFF00A884) : Colors.white60, fontSize: 12)),
                    ],
                  ),
                  subtitle: Row(
                    children: [
                      Expanded(child: Text(chat.lastMessage, style: const TextStyle(color: Colors.white60, fontSize: 14), maxLines: 1, overflow: TextOverflow.ellipsis)),
                      if (chat.unreadCount > 0)
                        Container(
                          margin: const EdgeInsets.only(left: 8),
                          padding: const EdgeInsets.all(6),
                          decoration: const BoxDecoration(color: Color(0xFF00A884), shape: BoxShape.circle),
                          child: Text('${chat.unreadCount}', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                    ],
                  ),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => ChatDetailView(device: widget.device, conversation: chat)),
                    ).then((_) => setState(() {
                      _conversationsFuture = _fetchConversations();
                    }));
                  },
                );
              },
            );
          },
        ),
      ),
    );
  }
}
