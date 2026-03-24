import 'package:flutter/material.dart';
import '../models/chat_models.dart';
import '../services/api_service.dart';

class ChatDetailView extends StatefulWidget {
  final DeviceModel device;
  final ConversationModel conversation;

  const ChatDetailView({super.key, required this.device, required this.conversation});

  @override
  State<ChatDetailView> createState() => _ChatDetailViewState();
}

class _ChatDetailViewState extends State<ChatDetailView> {
  final _api = ApiService();
  final _msgController = TextEditingController();
  final _scrollController = ScrollController();
  List<MessageModel> _messages = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchMessages();
  }

  Future<void> _fetchMessages() async {
    try {
      final res = await _api.getMessages(widget.device.id, widget.conversation.contactNumber);
      if (res.statusCode == 200) {
        setState(() {
          _messages = (res.data as List).map((e) => MessageModel.fromJson(e)).toList();
          _isLoading = false;
        });
        _scrollToBottom();
      }
    } catch (e) {
      debugPrint("Fetch Error: $e");
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });
  }

  void _sendMessage() async {
    final body = _msgController.text.trim();
    if (body.isEmpty) return;
    
    _msgController.clear();
    try {
      final res = await _api.sendMessage(widget.device.id, widget.conversation.contactNumber, body);
      if (res.statusCode == 200) {
        _fetchMessages();
      }
    } catch (e) {
       ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal mengirim: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B141A),
      appBar: AppBar(
        titleSpacing: 0,
        title: Row(
          children: [
            const CircleAvatar(radius: 18, backgroundColor: Colors.white12, child: Icon(Icons.person, color: Colors.white60)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(widget.conversation.contactName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const Text('Online', style: TextStyle(fontSize: 12, color: Colors.white60)),
                ],
              ),
            ),
          ],
        ),
        actions: [
          IconButton(icon: const Icon(Icons.label_outline), onPressed: () {
            // TODO: Show label selection dialog
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Manajemen Label akan segera hadir!')));
          }),
          IconButton(icon: const Icon(Icons.videocam), onPressed: () {}),
          IconButton(icon: const Icon(Icons.call), onPressed: () {}),
          IconButton(icon: const Icon(Icons.more_vert), onPressed: () {}),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: _isLoading 
              ? const Center(child: CircularProgressIndicator())
              : ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(16),
                  itemCount: _messages.length,
                  itemBuilder: (context, index) {
                    final msg = _messages[index];
                    final isMe = msg.direction == 'out';
                    return Align(
                      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.symmetric(vertical: 4),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: isMe ? const Color(0xFF005C4B) : const Color(0xFF1F2C34),
                          borderRadius: BorderRadius.only(
                            topLeft: const Radius.circular(12),
                            topRight: const Radius.circular(12),
                            bottomLeft: isMe ? const Radius.circular(12) : const Radius.circular(0),
                            bottomRight: isMe ? const Radius.circular(0) : const Radius.circular(12),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(msg.body, style: const TextStyle(color: Colors.white, fontSize: 16)),
                            const SizedBox(height: 4),
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(msg.time.split(' ').last.substring(0, 5), style: const TextStyle(color: Colors.white54, fontSize: 10)),
                                if (isMe) ...[
                                  const SizedBox(width: 4),
                                  Icon(Icons.done_all, size: 14, color: msg.status == 'read' ? Colors.blue : Colors.white54),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
          ),
          _buildInput(),
        ],
      ),
    );
  }

  Widget _buildInput() {
    return Container(
      padding: const EdgeInsets.fromLTRB(8, 0, 8, 12),
      color: Colors.transparent,
      child: Row(
        children: [
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(color: const Color(0xFF202C33), borderRadius: BorderRadius.circular(24)),
              child: Row(
                children: [
                  const Icon(Icons.emoji_emotions_outlined, color: Colors.white60),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextField(
                      controller: _msgController,
                      style: const TextStyle(color: Colors.white),
                      decoration: const InputDecoration(
                        hintText: 'Message',
                        hintStyle: TextStyle(color: Colors.white60),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  const Icon(Icons.attach_file, color: Colors.white60),
                  const SizedBox(width: 12),
                  const Icon(Icons.camera_alt, color: Colors.white60),
                ],
              ),
            ),
          ),
          const SizedBox(width: 6),
          FloatingActionButton(
            onPressed: _sendMessage,
            mini: true,
            backgroundColor: const Color(0xFF00A884),
            child: const Icon(Icons.send, color: Colors.white),
          ),
        ],
      ),
    );
  }
}
